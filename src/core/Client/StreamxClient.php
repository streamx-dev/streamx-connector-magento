<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client;

use Exception;
use Magento\Store\Api\Data\StoreInterface;
use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Publisher\Message;
use StreamX\ConnectorCore\Client\Model\Data;
use StreamX\ConnectorCore\Client\RabbitMQ\RabbitMqIngestionRequestsSender;
use StreamX\ConnectorCore\Traits\ExceptionLogger;

class StreamxClient {
    use ExceptionLogger;

    private const STREAMX_TYPE_PROPERTY_NAME = 'sx:type';

    private LoggerInterface $logger;
    private int $storeId;
    private string $storeCode;
    private RabbitMqIngestionRequestsSender $rabbitMqSender;

    public function __construct(
        LoggerInterface $logger,
        StoreInterface $store,
        RabbitMqIngestionRequestsSender $rabbitMqSender
    ) {
        $this->logger = $logger;
        $this->storeId = (int) $store->getId();
        $this->storeCode = $store->getCode();
        $this->rabbitMqSender = $rabbitMqSender;
    }

    public function publish(array $entities, string $indexerName): void {
        $publishMessages = [];
        foreach ($entities as $entity) {
            $entityType = EntityType::fromEntityAndIndexerName($entity, $indexerName);
            $key = $this->createStreamxKey($entityType, $entity['id']);
            $payload = new Data(json_encode($entity));
            $publishMessages[] = Message::newPublishMessage($key, $payload)
                ->withProperty(self::STREAMX_TYPE_PROPERTY_NAME, $entityType->getFullyQualifiedName())
                ->build();
        }

        $this->ingest($publishMessages, Message::PUBLISH_ACTION, $indexerName);
    }

    public function unpublish(array $entityIds, string $indexerName): void {
        $unpublishMessages = [];
        foreach ($entityIds as $entityId) {
            $entityType = EntityType::fromIndexerName($indexerName);
            $key = $this->createStreamxKey($entityType, (string) $entityId);
            $unpublishMessages[] = Message::newUnpublishMessage($key)
                ->withProperty(self::STREAMX_TYPE_PROPERTY_NAME, $entityType->getFullyQualifiedName())
                ->build();
        }

        $this->ingest($unpublishMessages, Message::UNPUBLISH_ACTION, $indexerName);
    }

    private function createStreamxKey(EntityType $entityType, string $entityId): string {
        return sprintf('%s_%s:%d',
            $this->storeCode,
            $entityType->getRootType(),
            $entityId
        );
    }

    /**
     * @param Message[] $ingestionMessages
     */
    private function ingest(array $ingestionMessages, string $action, string $indexerName): void {
        $keys = array_column($ingestionMessages, 'key');
        $messagesCount = count($ingestionMessages);
        $this->logger->info("Start sending to RabbitMQ $messagesCount $action entities from $indexerName with keys " . json_encode($keys));

        try {
            $this->rabbitMqSender->send($ingestionMessages, $this->storeId);
        } catch (Exception $e) {
            $this->logExceptionAsError('RabbitMQ sending exception', $e);
            // TODO: implement re-sender? store failed payloads on disk and periodically attempt to rabbitMqSender->send them?
        }

        $this->logger->info("Finished sending to RabbitMQ $messagesCount $action entities from $indexerName");
    }
}

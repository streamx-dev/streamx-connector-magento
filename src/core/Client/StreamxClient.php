<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client;

use Exception;
use Magento\Store\Api\Data\StoreInterface;
use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Publisher\Message;
use StreamX\ConnectorCore\Client\Model\Data;
use StreamX\ConnectorCore\Client\RabbitMQ\IngestionRequest;
use StreamX\ConnectorCore\Client\RabbitMQ\RabbitMqConfiguration;
use StreamX\ConnectorCore\Client\RabbitMQ\RabbitMqIngestionRequestsSender;
use StreamX\ConnectorCore\Traits\ExceptionLogger;

class StreamxClient {
    use ExceptionLogger;

    private const STREAMX_TYPE_PROPERTY_NAME = 'sx:type';

    private LoggerInterface $logger;
    private RabbitMqConfiguration $rabbitMqConfiguration;
    private RabbitMqIngestionRequestsSender $rabbitMqSender;
    private StreamxIngestor $streamxIngestor;

    public function __construct(
        LoggerInterface $logger,
        RabbitMqConfiguration $rabbitMqConfiguration,
        RabbitMqIngestionRequestsSender $rabbitMqSender,
        StreamxIngestor $streamxIngestor
    ) {
        $this->logger = $logger;
        $this->rabbitMqConfiguration = $rabbitMqConfiguration;
        $this->rabbitMqSender = $rabbitMqSender;
        $this->streamxIngestor = $streamxIngestor;
    }

    public function publish(array $entities, string $indexerId, StoreInterface $store): void {
        $publishMessages = [];
        foreach ($entities as $entity) {
            $entityType = EntityType::fromEntityAndIndexerId($entity, $indexerId);
            $key = $this->createStreamxKey($entityType, $entity['id'], $store);
            $payload = new Data(json_encode($entity));
            $publishMessages[] = Message::newPublishMessage($key, $payload)
                ->withProperty(self::STREAMX_TYPE_PROPERTY_NAME, $entityType->getFullyQualifiedName())
                ->build();
        }

        $this->ingest($publishMessages, Message::PUBLISH_ACTION, $indexerId, $store);
    }

    public function unpublish(array $entityIds, string $indexerId, StoreInterface $store): void {
        $unpublishMessages = [];
        foreach ($entityIds as $entityId) {
            $entityType = EntityType::fromIndexerId($indexerId);
            $key = $this->createStreamxKey($entityType, (string) $entityId, $store);
            $unpublishMessages[] = Message::newUnpublishMessage($key)
                ->withProperty(self::STREAMX_TYPE_PROPERTY_NAME, $entityType->getFullyQualifiedName())
                ->build();
        }

        $this->ingest($unpublishMessages, Message::UNPUBLISH_ACTION, $indexerId, $store);
    }

    private function createStreamxKey(EntityType $entityType, string $entityId, StoreInterface $store): string {
        return sprintf('%s_%s:%d',
            $store->getCode(),
            $entityType->getRootType(),
            $entityId
        );
    }

    /**
     * @param Message[] $ingestionMessages
     */
    protected function ingest(array $ingestionMessages, string $action, string $indexerId, StoreInterface $store): void {
        $keys = array_column($ingestionMessages, 'key');
        $messagesCount = count($ingestionMessages);
        $this->logger->info("Start sending $messagesCount $action entities from $indexerId with keys " . json_encode($keys));
        $storeId = (int) $store->getId();

        try {
            if ($this->rabbitMqConfiguration->isEnabled()) {
                $this->rabbitMqSender->send(new IngestionRequest($ingestionMessages, $storeId));
            } else {
                $this->streamxIngestor->send($ingestionMessages, $storeId);
            }
        } catch (Exception $e) {
            $this->logExceptionAsError('Message sending exception', $e);
        }

        $this->logger->info("Finished sending $messagesCount $action entities from $indexerId");
    }
}

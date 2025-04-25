<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client;

use Exception;
use Magento\Store\Api\Data\StoreInterface;
use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Publisher\Message;
use Streamx\Clients\Ingestion\Publisher\Publisher;
use StreamX\ConnectorCore\Client\Model\Data;
use StreamX\ConnectorCore\Traits\ExceptionLogger;

class StreamxClient {
    use ExceptionLogger;

    private const STREAMX_TYPE_PROPERTY_NAME = 'sx:type';

    private LoggerInterface $logger;
    private string $storeCode;
    private Publisher $dataIngestor;

    public function __construct(
        LoggerInterface $logger,
        StreamxClientConfiguration $configuration,
        StoreInterface $store
    ) {
        $this->logger = $logger;
        $this->storeCode = $store->getCode();
        $storeId = (int) $store->getId();
        $this->dataIngestor = StreamxPublisherFactory::createStreamxPublisher($configuration, $storeId, true);
    }

    public function publish(array $entities, string $indexerId): void {
        $publishMessages = [];
        foreach ($entities as $entity) {
            $entityType = EntityType::fromEntityAndIndexerId($entity, $indexerId);
            $key = $this->createStreamxKey($entityType, $entity['id']);
            $payload = new Data(json_encode($entity));
            $publishMessages[] = Message::newPublishMessage($key, $payload)
                ->withProperty(self::STREAMX_TYPE_PROPERTY_NAME, $entityType->getFullyQualifiedName())
                ->build();
        }

        $this->ingest($publishMessages, "publishing", $indexerId);
    }

    public function unpublish(array $entityIds, string $indexerId): void {
        $unpublishMessages = [];
        foreach ($entityIds as $entityId) {
            $entityType = EntityType::fromIndexerId($indexerId);
            $key = $this->createStreamxKey($entityType, (string) $entityId);
            $unpublishMessages[] = Message::newUnpublishMessage($key)
                ->withProperty(self::STREAMX_TYPE_PROPERTY_NAME, $entityType->getFullyQualifiedName())
                ->build();
        }

        $this->ingest($unpublishMessages, "unpublishing", $indexerId);
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
    private function ingest(array $ingestionMessages, string $operationName, string $indexerId): void {
        $keys = array_column($ingestionMessages, 'key');
        $messagesCount = count($ingestionMessages);
        $this->logger->info("Start $operationName $messagesCount entities from $indexerId with keys " . json_encode($keys));

        try {
            // TODO make sure this will never block. Best by turning off Pulsar container. Migrate to RabbitMQ to handle ingestion asynchronously (and receive NAK/ACK based retry features)
            $messageStatuses = $this->dataIngestor->sendMulti($ingestionMessages);

            foreach ($messageStatuses as $messageStatus) {
                if ($messageStatus->getSuccess() === null) {
                    $this->logger->error('Ingestion failure: ' . json_encode($messageStatus->getFailure()));
                }
            }
        } catch (Exception $e) {
            $this->logExceptionAsError('Ingestion exception', $e);
        }

        $this->logger->info("Finished $operationName $messagesCount entities from $indexerId");
    }
}

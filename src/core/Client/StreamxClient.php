<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client;

use Exception;
use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Publisher\Message;
use Streamx\Clients\Ingestion\Publisher\Publisher;
use StreamX\ConnectorCatalog\Model\Indexer\CategoryProcessor;
use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCore\Client\Model\Data;

class StreamxClient {

    private LoggerInterface $logger;
    private Publisher $publisher;
    private string $productKeyPrefix;
    private string $categoryKeyPrefix;

    public function __construct(
        LoggerInterface $logger,
        Publisher $publisher,
        string $productKeyPrefix,
        string $categoryKeyPrefix
    ) {
        $this->logger = $logger;
        $this->publisher = $publisher;
        $this->productKeyPrefix = $productKeyPrefix;
        $this->categoryKeyPrefix = $categoryKeyPrefix;
    }

    public function publish(array $entities, string $indexerName): void {
        $entityCount = count($entities);
        $this->logger->info("Start publishing $entityCount entities from $indexerName");

        $publishMessages = [];
        foreach ($entities as $entity) {
            $key = self::createStreamxKey($indexerName, $entity['id']);
            $payload = new Data(json_encode($entity));
            $publishMessages[] = Message::newPublishMessage($key, $payload)->build();
        }

        $this->ingest($publishMessages);
        $this->logger->info("Finished publishing $entityCount entities from $indexerName");
    }

    public function unpublish(array $entityIds, string $indexerName): void {
        $entityCount = count($entityIds);
        $this->logger->info("Start unpublishing $entityCount entities");

        $unpublishMessages = [];
        foreach ($entityIds as $entityId) {
            $key = self::createStreamxKey($indexerName, $entityId);
            $unpublishMessages[] = Message::newUnpublishMessage($key)->build();
        }

        $this->ingest($unpublishMessages);
        $this->logger->info("Finished unpublishing $entityCount entities");
    }

    private function createStreamxKey(string $indexerName, int $entityId): string {
        if ($indexerName == ProductProcessor::INDEXER_ID) {
            return $this->productKeyPrefix . $entityId;
        }
        if ($indexerName == CategoryProcessor::INDEXER_ID) {
            return $this->categoryKeyPrefix . $entityId;
        }
        throw new Exception("Received data from unexpected indexer: $indexerName");
    }

    /**
     * @param Message[] $ingestionMessages
     */
    private function ingest(array $ingestionMessages): void {
        $keys = array_column($ingestionMessages, 'key');
        $this->logger->info("Ingesting entities with keys " . json_encode($keys));

        try {
            // TODO make sure this will never block. Best by turning off Pulsar container
            $messageStatuses = $this->publisher->sendMulti($ingestionMessages);

            foreach ($messageStatuses as $messageStatus) {
                if ($messageStatus->getSuccess() === null) {
                    $this->logger->error('Ingestion failure: ' . json_encode($messageStatus->getFailure()));
                }
            }
        } catch (Exception $e) {
            $this->logger->error('Ingestion exception: ' . $e->getMessage(), ['exception' => $e]);
        }
    }

    public function isStreamxAvailable(): bool {
        try {
            $schema = $this->publisher->fetchSchema();
            if (str_contains($schema, 'IngestionMessage')) {
                return true;
            }
            $this->logger->error("Requested StreamX channel is not available, Ingestion Message definition is missing in schema:\n$schema");
        } catch (Exception $e) {
            $this->logger->error('Exception checking if StreamX is available: ' . $e->getMessage(), ['exception' => $e]);
        }
        return false;
    }
}

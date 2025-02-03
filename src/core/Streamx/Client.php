<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Streamx;

use Exception;
use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use Streamx\Clients\Ingestion\Publisher\Message;
use Streamx\Clients\Ingestion\Publisher\Publisher;
use StreamX\ConnectorCatalog\Model\Indexer\AttributeProcessor;
use StreamX\ConnectorCatalog\Model\Indexer\CategoryProcessor;
use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCore\Api\Client\ClientInterface;
use StreamX\ConnectorCore\Streamx\Model\Data;

class Client implements ClientInterface {

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

    public function ingest(array $bulkOperations): void {
        $operationsCount = count($bulkOperations);
        $this->logger->info("Start ingesting $operationsCount operations");

        $ingestionMessages = array_map(
            function (array $item) {
                return $this->mapToIngestionMessage($item);
            },
            $bulkOperations
        );

        if (!empty($ingestionMessages)) {
            $this->ingestToStreamX($ingestionMessages);
        }
        $this->logger->info("Finished ingesting $operationsCount operations");
    }

    private function mapToIngestionMessage(array $item): Message {
        if (isset($item['publish'])) {
            return $this->createPublishMessage($item['publish']);
        }
        if (isset($item['unpublish'])) {
            return $this->createUnpublishMessage($item['unpublish']);
        }
        throw new Exception('Unexpected bulk item type: ' . json_encode($item, JSON_PRETTY_PRINT));
    }

    private function createPublishMessage(array $publishItem): Message {
        $indexerName = $publishItem['indexer_name'];
        $entity = $publishItem['entity'];
        $entityId = $entity['id'];
        $key = self::createStreamxKey($indexerName, $entityId);
        $payload = new Data(json_encode($entity));
        return Message::newPublishMessage($key, $payload)->build();
    }

    private function createUnpublishMessage(array $unpublishItem): Message {
        $indexerName = $unpublishItem['indexer_name'];
        $entityId = $unpublishItem['id'];
        $key = self::createStreamxKey($indexerName, $entityId);
        return Message::newUnpublishMessage($key)->build();
    }

    private function createStreamxKey(string $indexerName, int $entityId): string {
        switch ($indexerName) {
            case ProductProcessor::INDEXER_ID:
                return $this->productKeyPrefix . $entityId;
            case CategoryProcessor::INDEXER_ID:
                return $this->categoryKeyPrefix . $entityId;
            case AttributeProcessor::INDEXER_ID: // TODO: in future remove this. Attribute definition change will trigger republishing products that use the attribute
                return "attr:$entityId";
            default:
                throw new Exception("Unexpected entity type: $indexerName");
        }
    }

    /**
     * @param Message[] $messages Ingestion messages
     */
    private function ingestToStreamX(array $messages): void {
        try {
            // TODO make sure this will never block. Best by turning off Pulsar container
            $messageStatuses = $this->publisher->sendMulti($messages);

            foreach ($messageStatuses as $messageStatus) {
                if ($messageStatus->getSuccess() === null) {
                    $this->logger->error('Ingestion failure: ' . json_encode($messageStatus->getFailure()));
                }
            }
        } catch (StreamxClientException $e) {
            $this->logger->error('Ingestion exception: ' . $e->getMessage(), ['exception' => $e]);
        }
    }

    public function isStreamxAvailable(): bool {
        try {
            $schema = $this->publisher->fetchSchema();
            return str_contains($schema, 'IngestionMessage');
        } catch (Exception $e) {
            $this->logger->error('Exception checking if StreamX is available: ' . $e->getMessage(), ['exception' => $e]);
            return false;
        }
    }
}

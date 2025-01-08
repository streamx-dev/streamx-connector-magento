<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Streamx;

use Exception;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use Streamx\Clients\Ingestion\Publisher\Message;
use Streamx\Clients\Ingestion\Publisher\Publisher;
use StreamX\ConnectorCore\Api\Client\ClientInterface;
use StreamX\ConnectorCore\Streamx\Model\Data;

class Client implements ClientInterface {

    private LoggerInterface $logger;
    private Publisher $publisher;

    // TODO use baseUrl to prepend to image paths, which are retuned as relative paths, like:
    //  $baseImageUrl = $this->baseUrl . "media/catalog/product";
    //  $fullImageUrl = $baseImageUrl . $data['image']
    private string $baseUrl;

    public function __construct(LoggerInterface $logger, Publisher $publisher, StoreManagerInterface $storeManager) {
        $this->logger = $logger;
        $this->publisher = $publisher;
        $this->baseUrl = $storeManager->getStore()->getBaseUrl();
    }

    // TODO: adjust code that produced the $bulkOperations array, to make it in StreamX format (originally it is in ElasticSearch format)
    public function ingest(array $bulkOperations): void {
        $operationsCount = count($bulkOperations);
        $this->logger->info("Start ingesting $operationsCount operations");

        $ingestionMessages = array_map(
            function (array $item) {
                return self::mapToIngestionMessage($item);
            },
            $bulkOperations
        );

        if (!empty($ingestionMessages)) {
            $this->ingestToStreamX($ingestionMessages);
        }
        $this->logger->info("Finished ingesting $operationsCount operations");
    }

    private static function mapToIngestionMessage(array $item): Message {
        if (isset($item['publish'])) {
            return self::createPublishMessage($item['publish']);
        }
        if (isset($item['unpublish'])) {
            return self::createUnpublishMessage($item['unpublish']);
        }
        throw new Exception('Unexpected bulk item type: ' . json_encode($item, JSON_PRETTY_PRINT));
    }

    private static function createPublishMessage(array $publishItem): Message {
        $entityType = $publishItem['type'];
        $entity = $publishItem['entity'];
        $entityId = $entity['id'];
        $key = self::createStreamxEntityKey($entityType, $entityId);
        $payload = new Data(json_encode($entity));
        return Message::newPublishMessage($key, $payload)->build();
    }

    private static function createUnpublishMessage(array $unpublishItem): Message {
        $entityType = $unpublishItem['type'];
        $entityId = $unpublishItem['id'];
        $key = self::createStreamxEntityKey($entityType, $entityId);
        return Message::newUnpublishMessage($key)->build();
    }

    private static function createStreamxEntityKey(string $entityType, int $entityId): string {
        return $entityType . '_' . $entityId;
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
            return $this->publisher->isIngestionServiceAvailable();
        } catch (Exception $e) {
            $this->logger->error('Exception checking if StreamX is available: ' . $e->getMessage(), ['exception' => $e]);
            return false;
        }
    }
}

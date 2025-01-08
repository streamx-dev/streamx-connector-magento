<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Streamx;

use Exception;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use Streamx\Clients\Ingestion\Publisher\Message;
use Streamx\Clients\Ingestion\Publisher\Publisher;
use StreamX\ConnectorCore\Api\Client\ClientInterface;
use StreamX\ConnectorCore\Streamx\Model\Data;

class Client implements ClientInterface {

    private Publisher $publisher;
    private LoggerInterface $logger;
    private ?int $storeId;

    // TODO use baseUrl to prepend to image paths, which are retuned as relative paths, like:
    //  $baseImageUrl = $this->baseUrl . "media/catalog/product";
    //  $fullImageUrl = $baseImageUrl . $data['image']
    private string $baseUrl;

    public function __construct(StoreManagerInterface $storeManager, Publisher $publisher, LoggerInterface $logger) {
        $this->logger = $logger;
        $this->publisher = $publisher;
        try {
            $store = $storeManager->getStore();
            $this->storeId = (int) $store->getId();
            $this->baseUrl = $store->getBaseUrl();
        } catch (NoSuchEntityException $e) {
            $this->storeId = null;
            $this->baseUrl = '';
            $this->logger->error("Cannot get store id and base url" . $e->getMessage());
        }
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
        $this->logger->info("SUPPRESSING:: Checking if StreamX is available");
        // TODO: implement rest-ingestion service availability check
        return true;
    }
}

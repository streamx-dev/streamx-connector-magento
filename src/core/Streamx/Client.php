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
    public function bulk(array $bulkOperations): array {
        $this->logger->info("EXECUTING:: bulk");

        foreach ($bulkOperations as $item) {
            if (isset($item['unpublish'])) {
                $entityType = $item['unpublish']['type']; // product, category or attribute
                $entityId = $item['unpublish']['id'];
                $key = $this->createStreamxEntityKey($entityType, $entityId);
                try {
                    $this->unpublishFromStreamX($key);
                } catch (StreamxClientException $e) {
                    $this->logger->error("Unpublishing $key from StreamX failed: " . $e->getMessage(), ['exception' => $e]);
                }
            }

            else if (isset($item['publish'])) {
                $entityType = $item['publish']['type']; // product, category or attribute
                $entity = $item['publish']['entity'];
                $key = $this->createStreamxEntityKey($entityType, $entity['id']);
                try {
                    $this->publishToStreamX($key, json_encode($entity)); // TODO make sure this will never block. Best by turning off Pulsar container
                } catch (StreamxClientException $e) {
                    $this->logger->error("Publishing $key to StreamX failed: " . $e->getMessage(), ['exception' => $e]);
                }
            } else {
                throw new Exception('Unexpected bulk item type: ' . json_encode($item, JSON_PRETTY_PRINT));
            }
        }

        return ['items' => [], 'errors' => ""]; // TODO don't need to return anything
    }

    private static function createStreamxEntityKey(string $entityType, int $entityId): string {
        return $entityType . '_' . $entityId;
    }

    /**
     * @throws StreamxClientException
     */
    private function publishToStreamX(string $key, string $payload) {
        $this->logger->info("Publishing $key");

        $data = new Data($payload);
        $message = Message::newPublishMessage($key, $data)->build();
        $messageStatuses = $this->publisher->sendMulti([$message]);

        $messageStatus = $messageStatuses[0]; // TODO implement sending batches of messages at once
        // TODO: send as much messages from the batch as possible at once, to not reach the limit of body size of a single request

        if ($messageStatus->getSuccess() === null) {
            $this->logger->error("Error response from sending $key: " . json_encode($messageStatus->getFailure()));
        }
    }

    /**
     * @throws StreamxClientException
     */
    private function unpublishFromStreamX(string $key) {
        $this->logger->info("Unpublishing $key");
        $this->publisher->unpublish($key);
    }

    public function getClustersHealth(): array {
        $this->logger->info("SUPPRESSING:: Checking cluster health");
        // TODO: implement rest-ingestion service availability check
        return [['status' => 'green']];
    }
}

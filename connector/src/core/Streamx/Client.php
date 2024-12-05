<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Streamx;

use StreamX\ConnectorCore\Api\Client\ClientInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use Streamx\Clients\Ingestion\Publisher\Publisher;

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

    // TODO: adjust code that produced the $bulkParams array, to make it in StreamX format (originally it is in ElasticSearch format)
    public function bulk(array $bulkParams): array {
        $this->logger->info("EXECUTING:: bulk");

        if (!isset($bulkParams['body'][0]['index'])) {
            // TODO diagnose why such inputs may be received by the method
            return [];
        }

        $type = $bulkParams['body'][0]['index']['_type']; // product, category ... // TODO diagnose what are all possible types
        foreach ($bulkParams['body'] as $data) {
            if (!isset($data['id'])) {
                // TODO diagnose why such inputs may be received by the method
                continue;
            }
            $key = $type . '_' . $data['id'];
            try {
                $this->publishToStreamX($key, json_encode($data)); // TODO make sure this is async
            } catch (StreamxClientException $e) {
                $this->logger->error('Data update failed: ' . $e->getMessage(), ['exception' => $e]);
            }
        }

        return ['items' => [], 'errors' => ""];
    }

    /**
     * @throws StreamxClientException
     */
    private function publishToStreamX(string $key, string $payload) {
        $this->logger->info("Publishing product $key");
        $data = [
            'content' => [
                'bytes' => $payload
            ]
        ];
        $this->publisher->publish($key, $data);
    }

    public function getClustersHealth(): array {
        $this->logger->info("SUPPRESSING:: Checking cluster health");
        // TODO: implement rest-ingestion service availability check
        return [['status' => 'green']];
    }

    public function deleteByQuery(array $params) {
        // TODO: implement unpublishing
        $this->logger->info("SUPPRESSING:: deleting by query: " . json_encode($params));
    }
}

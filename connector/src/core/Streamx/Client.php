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

    public function bulk(array $bulkParams): array {
        $this->logger->info("EXECUTING:: bulk update");

        // TODO: adjust code that produced the $bulkParams array, to make it in StreamX format (originally it is in ElasticSearch format)
        $type = $bulkParams['body'][0]['index']['_type']; // product, category ...
        foreach ($bulkParams['body'] as $data) {
            if (isset($data['index']['_index'])) {
                // Skip the first element with index definition
                continue;
            }
            if ($type == 'product') {
                $payloadData = $this->mapProductData($data);
                $this->logger->info("Data update requested");
                try {
                    $key = 'product_' . $payloadData['id'];
                    $this->publishToStreamX($key, json_encode($payloadData)); // TODO make sure this is async
                    $this->logger->info("Data update processed");
                } catch (StreamxClientException $e) {
                    $this->logger->error('Data update failed: ' . $e->getMessage(), ['exception' => $e]);
                }
            } else {
                $this->logger->info("Not a product: $type");
            }
        }

        return ['items' => [], 'errors' => ""]; // $this->client->bulk($bulkParams);
    }

    private function mapProductData($data): array {
        $payloadData = [];
        $payloadData['id'] = $data['sku'];
        $payloadData['name'] = $data['name'];
        if (isset($data['url_path'])) {
            $payloadData['urlSafeName'] = $data['url_path'];
        }

        // Images
        $baseImageUrl = $this->baseUrl . "media/catalog/product";
        if (isset($data['image'])) {
            $payloadData['mainImage'] = $baseImageUrl . $data['image'];
        }
        $images = [];
        foreach ($data['media_gallery'] as $image) {
            if (isset($image['image'])) {
                $images[] = $baseImageUrl . $image['image'];
            }
        }
        $payloadData['images'] = $images;

        if (isset($data['final_price'])) {
            $payloadData['price'] = $data['final_price'];
        }

        if (isset($data['short_description'])) {
            $payloadData['description'] = $data['short_description'];
        } else {
            $payloadData['description'] = substr($data['description'], 0, 1600);
        }
        if (isset($data['category']['name'])) {
            $payloadData['category'] = $data['category']['name'];
        }

        return $payloadData;
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
        return [['status' => 'green']];
    }

    public function getMasterMaxQueueSize(): int {
        $this->logger->info("SUPPRESSING:: checking for max queue size. Returning 10000");
        return 10000;
    }

    public function deleteByQuery(array $params) {
        $this->logger->info("SUPPRESSING:: deleting by query: " . json_encode($params));
    }
}

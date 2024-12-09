<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Streamx;

use Exception;
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

        // In the full reindex mode:
        // The products are delivered to this method in bulks of max 1000 items. Currently, there are ca. 2050 items, so we receive 3 batches from full reindex.
        // $bulkParams array comes in pairs: item 0 is "index": { "_type": "product", "_id": 14 (which is also the product id) }
        // item 1 is the product data array
        // item 2 is the index for second product, item 3 is the second product data -> and so on

        // In the update on save mode (when a single product is updated via Magento UI):
        // We receive the same format, but only one pair of array items: 0 = index definition and 1 = the product
        // -> This is used to send updates for Products, Categories and Attributes

        // When installing the connector, automatic reindex of all is performed, but it contains additional items:
        // 0 is "update" and 1 is "doc" (and so on, they come in pairs)
        // The "_type" of "update" is always "product".
        // And "doc" contains most important fields of the product and list of its categories (id/name/position of each)
        // -> This is used to send updates for Product Categories

        $bodyArray = $bulkParams['body'];

        // The var serves to store types of the even items to correctly interpret the odd ones
        $entityType = null;

        for ($i = 0; $i < count($bodyArray); $i++) {
            $item = $bodyArray[$i];
            if ($i % 2 == 0) {
                if (isset($item['update'])) {
                    $entityType = 'product_category'; // TODO add validation that we expect $item['doc']['_type'] == 'product'
                } else if (isset($item['index'])) {
                    $entityType = $item['index']['_type']; // product, category or attribute
                } else {
                    throw new Exception(json_encode($item, JSON_PRETTY_PRINT));
                }
            } else {
                if (isset($item['doc'])) {
                    $entity = $item['doc'];
                    // TODO add test for publishing product categories
                } else {
                    $entity = $item;
                }

                $key = $entityType . '_' . $entity['id'];
                try {
                    $this->publishToStreamX($key, json_encode($entity)); // TODO make sure this will never block
                } catch (StreamxClientException $e) {
                    $this->logger->error('Data update failed: ' . $e->getMessage(), ['exception' => $e]);
                }
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

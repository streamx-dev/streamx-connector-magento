<?php declare(strict_types=1);

namespace Divante\VsbridgeIndexerCore\Streamx;

use Divante\VsbridgeIndexerCore\Api\Client\ClientInterface;
use Exception;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class Client implements ClientInterface {
    private $headers = [
        'Content-Type' => 'application/json',
        'accept' => '*/*'
    ];
    private \GuzzleHttp\Client $client;
    private LoggerInterface $logger;
    private string $baseUrl;

    public function __construct(StoreManagerInterface $storeManager, \GuzzleHttp\Client $client, LoggerInterface $logger) {
        $this->client = $client;
        $this->logger = $logger;
        try {
            $this->baseUrl = $storeManager->getStore()->getBaseUrl();
        } catch (NoSuchEntityException $e) {
            $this->baseUrl = "";
            $this->logger->error("Cannot get base url" . $e->getMessage());
        }
    }

    public function bulk(array $bulkParams): array {
        $this->logger->info("EXECUTING:: bulk update");

        $type = $bulkParams['body'][0]['index']['_type']; // product, category ...
        foreach ($bulkParams['body'] as $data) {
            if (isset($data['index']['_index'])) {
                // Skip the first element with index definition
                continue;
            }
            if ($type == 'product') {
                $payloadData = $this->mapProductData($data);

                $content = new ContentTemplate();
                $content->setType("application/json");
                $content->setPayload(json_encode($payloadData));

                $body = new BodyTemplate();
                $body->setKey("pim:" . $payloadData['id']);
                $body->setType("data");
                $body->setDependencies([]);
                $body->setContent($content);

                // Do the call
                $bodyEncoded = json_encode($body);
                $this->logger->info("Data update requested");
                $request = new Request('POST', "", $this->headers, $bodyEncoded);
                try {
                    $promise = $this->client->sendAsync($request);
                    $promise->then(
                        function (ResponseInterface $response) {
                            $this->logger->info("Data update processed: " . $response->getBody());
                        },
                        function (RequestException $exception) {
                        }
                    );
                    // TODO this is required, since Guzzle does not support fire and forget mechanism
                    // We should reimplement a client to use something more performant than HTTP (GRPC? or bash client)
                    $promise->wait();
                } catch (Exception $e) {
                    $this->logger->warning("Data update failed: " . $e->getMessage());
                }
            }
        }

        return ['items' => [], 'errors' => ""]; // $this->client->bulk($bulkParams);
    }

    private function mapProductData($data): array {
        $payloadData = [];
        $payloadData['id'] = $data['sku'];
        $payloadData['name'] = $data['name'];
        $payloadData['urlSafeName'] = $data['url_path'];

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

    public function changeRefreshInterval(string $indexName, $value) {
        $this->logger->info("SUPPRESSING:: changing refresh interval of $indexName to $value");
    }

    public function changeNumberOfReplicas(string $indexName, int $value) {
        $this->logger->info("SUPPRESSING:: changing number of replicas of $indexName to $value");
    }

    public function createIndex(string $indexName, array $indexSettings) {
        $this->logger->info("SUPPRESSING:: creation of an index with name $indexName and data: " . json_encode($indexSettings));
    }

    public function getClustersHealth(): array {
        $this->logger->info("SUPPRESSING:: Checking cluster health");
        return [['status' => 'green']];
    }

    public function getIndicesNameByAlias(string $indexAlias): array {
        $this->logger->info("SUPPRESSING:: getting index name by alias: $indexAlias");
        return [];
    }

    public function getIndexSettings(string $indexName): array {
        $this->logger->info("SUPPRESSING:: getting index settings for $indexName");
        return [];
    }

    public function getMasterMaxQueueSize(): int {
        $this->logger->info("SUPPRESSING:: checking for max queue size. Returning 10000");
        return 10000;
    }

    public function updateAliases(array $aliasActions) {
        $this->logger->info("SUPPRESSING:: alias update: " . json_encode($aliasActions));
    }

    public function refreshIndex(string $indexName) {
        $this->logger->info("SUPPRESSING:: index refresh: $indexName");
    }

    public function indexExists(string $indexName): bool {
        $this->logger->info("SUPPRESSING:: check of an index availability: $indexName");
        return true;
    }

    public function deleteIndex(string $indexName): array {
        $this->logger->info("SUPPRESSING:: index removal: $indexName");
        return [];
    }

    public function putMapping(string $indexName, string $type, array $mapping) {
        $requestPayload = [
            'index' => $indexName,
            'type' => $type,
            'body' => [$type => $mapping]
        ];
        $this->logger->info("SUPPRESSING:: mapping propagation: " . json_encode($requestPayload));
    }

    public function deleteByQuery(array $params) {
        $this->logger->info("SUPPRESSING:: deleting by query: " . json_encode($params));
    }
}

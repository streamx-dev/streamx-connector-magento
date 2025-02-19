<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client;

use Exception;
use GuzzleHttp\Client as GuzzleHttpClient;
use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Builders\StreamxClientBuilders;
use Streamx\Clients\Ingestion\Publisher\Message;
use Streamx\Clients\Ingestion\Publisher\Publisher;
use StreamX\ConnectorCatalog\Model\Indexer\CategoryProcessor;
use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCore\Client\Model\Data;

class StreamxClient {

    private LoggerInterface $logger;

    private string $ingestionBaseUrl;
    private string $channelName;
    private string $channelSchemaName;
    private string $productKeyPrefix;
    private string $categoryKeyPrefix;
    private ?string $authToken;
    private bool $shouldDisableCertificateValidation;
    private Publisher $schemasFetcher;
    private Publisher $dataIngestor;

    public function __construct(
        LoggerInterface $logger,
        StreamxClientConfiguration $configuration,
        int $storeId
    ) {
        $this->logger = $logger;
        $this->ingestionBaseUrl = $configuration->getIngestionBaseUrl($storeId);
        $this->channelName = $configuration->getChannelName($storeId);
        $this->channelSchemaName = $configuration->getChannelSchemaName($storeId);
        $this->productKeyPrefix = $configuration->getProductKeyPrefix($storeId);
        $this->categoryKeyPrefix = $configuration->getCategoryKeyPrefix($storeId);
        $this->authToken = $configuration->getAuthToken($storeId);
        $this->shouldDisableCertificateValidation = $configuration->shouldDisableCertificateValidation($storeId);
        $this->schemasFetcher = $this->createStreamxPublisher(false);
        $this->dataIngestor = $this->createStreamxPublisher(true);
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
            $messageStatuses = $this->dataIngestor->sendMulti($ingestionMessages);

            foreach ($messageStatuses as $messageStatus) {
                if ($messageStatus->getSuccess() === null) {
                    $this->logger->error('Ingestion failure: ' . json_encode($messageStatus->getFailure()));
                }
            }
        } catch (Exception $e) {
            $this->logException('Ingestion exception', $e);
        }
    }

    public function isStreamxAvailable(): bool {
        try {
            $schema = $this->schemasFetcher->fetchSchema();
            if (str_contains($schema, 'IngestionMessage')) {
                return true;
            }
            $this->logger->error("Requested StreamX channel is not available, Ingestion Message definition is missing in schema:\n$schema");
        } catch (Exception $e) {
            $this->logException('Exception checking if StreamX is available', $e);
        }
        return false;
    }

    private function createStreamxPublisher(bool $stream): Publisher {
        $httpClient = new GuzzleHttpClient([
            'connect_timeout' => 1, // maximum time (in seconds) to establish the connection
            'timeout' => 5, // maximum time (in seconds) to wait for response
            'verify' => !$this->shouldDisableCertificateValidation,
            'stream' => $stream
        ]);

        $ingestionClientBuilder = StreamxClientBuilders::create($this->ingestionBaseUrl)
            ->setHttpClient($httpClient);

        if ($this->authToken) {
            $ingestionClientBuilder->setAuthToken($this->authToken);
        }

        return $ingestionClientBuilder->build()->newPublisher(
            $this->channelName,
            $this->channelSchemaName
        );
    }

    private function logException(string $customMessage, Exception $e): void {
        $this->logger->error(
            $customMessage . ': ' . $e->getMessage(),
            [
                'Exception' => $e,
                'Stack trace' => $e->getTraceAsString(),
            ]
        );
    }
}

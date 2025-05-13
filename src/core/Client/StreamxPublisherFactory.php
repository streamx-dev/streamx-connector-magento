<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client;

use GuzzleHttp\Client as GuzzleHttpClient;
use Streamx\Clients\Ingestion\Builders\StreamxClientBuilders;
use Streamx\Clients\Ingestion\Publisher\Publisher;

class StreamxPublisherFactory {

    private StreamxClientConfiguration $clientConfiguration;
    private array $streamxPublishersCache = []; // key: storeId_streamFlag

    public function __construct(StreamxClientConfiguration $clientConfiguration) {
        $this->clientConfiguration = $clientConfiguration;
    }

    public function getOrCreateStreamxPublisher(int $storeId, bool $streamFlag): Publisher {
        $cacheKey = sprintf('%s_%s', $storeId, $streamFlag ? 'true' : 'false');
        if (!isset($this->streamxPublishersCache[$cacheKey])) {
            $this->streamxPublishersCache[$cacheKey] = self::createStreamxPublisher($this->clientConfiguration, $storeId, $streamFlag);
        }
        return $this->streamxPublishersCache[$cacheKey];
    }

    private function createStreamxPublisher(StreamxClientConfiguration $configuration, int $storeId, bool $streamFlag): Publisher {
        $httpClient = new GuzzleHttpClient([
            'connect_timeout' => 1, // maximum time (in seconds) to establish the connection
            'timeout' => 5, // maximum time (in seconds) to wait for response
            'verify' => !$configuration->shouldDisableCertificateValidation($storeId),
            'stream' => $streamFlag
        ]);

        $ingestionClientBuilder = StreamxClientBuilders::create($configuration->getIngestionBaseUrl($storeId))
            ->setHttpClient($httpClient);

        $authToken = $configuration->getAuthToken($storeId);
        if ($authToken) {
            $ingestionClientBuilder->setAuthToken($authToken);
        }

        return $ingestionClientBuilder->build()->newPublisher(
            $configuration->getChannelName($storeId),
            $configuration->getChannelSchemaName($storeId)
        );
    }

}
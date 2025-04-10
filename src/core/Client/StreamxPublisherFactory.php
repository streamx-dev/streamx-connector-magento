<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client;

use GuzzleHttp\Client as GuzzleHttpClient;
use Streamx\Clients\Ingestion\Builders\StreamxClientBuilders;
use Streamx\Clients\Ingestion\Publisher\Publisher;

class StreamxPublisherFactory {

    private function __construct() {
        // no instances
    }

    public static function createStreamxPublisher(StreamxClientConfiguration $configuration, int $storeId, bool $stream): Publisher {
        $httpClient = new GuzzleHttpClient([
            'connect_timeout' => 1, // maximum time (in seconds) to establish the connection
            'timeout' => 5, // maximum time (in seconds) to wait for response
            'verify' => !$configuration->shouldDisableCertificateValidation($storeId),
            'stream' => $stream
        ]);

        $ingestionClientBuilder = StreamxClientBuilders::create($configuration->getIngestionBaseUrl($storeId))
            ->setHttpClient($httpClient);

        if ($configuration->getAuthToken($storeId)) {
            $ingestionClientBuilder->setAuthToken($configuration->getAuthToken($storeId));
        }

        return $ingestionClientBuilder->build()->newPublisher(
            $configuration->getChannelName($storeId),
            $configuration->getChannelSchemaName($storeId)
        );
    }

}
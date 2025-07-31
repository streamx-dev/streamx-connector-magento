<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client;

use GuzzleHttp\Client as GuzzleHttpClient;
use Streamx\Clients\Ingestion\Builders\StreamxClientBuilders;

class StreamxPublisherFactory {

    private StreamxClientConfiguration $clientConfiguration;

    public function __construct(StreamxClientConfiguration $clientConfiguration) {
        $this->clientConfiguration = $clientConfiguration;
    }

    public function createStreamxPublisher(int $storeId): StreamxPublisher {
        $configuration = $this->clientConfiguration;

        $httpClient = new GuzzleHttpClient([
            'connect_timeout' => $configuration->getConnectionTimeout($storeId),
            'timeout' => $configuration->getResponseTimeout($storeId),
            'verify' => !$configuration->shouldDisableCertificateValidation($storeId),
            'stream' => 'true'
        ]);

        $baseUrl = $configuration->getIngestionBaseUrl($storeId);
        $ingestionClientBuilder = StreamxClientBuilders::create($baseUrl)->setHttpClient($httpClient);

        $authToken = $configuration->getAuthToken($storeId);
        if ($authToken) {
            $ingestionClientBuilder->setAuthToken($authToken);
        }

        $publisher = $ingestionClientBuilder->build()->newPublisher(
            $configuration->getChannelName($storeId),
            $configuration->getChannelSchemaName($storeId)
        );

        return new StreamxPublisher($publisher, $baseUrl);
    }

}
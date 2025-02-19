<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client;

use GuzzleHttp\Client as GuzzleHttpClient;
use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Builders\StreamxClientBuilders;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use Streamx\Clients\Ingestion\StreamxClient as IngestionClient;

class StreamxClientProvider {

    private LoggerInterface $logger;
    private StreamxClientConfiguration $configuration;

    public function __construct(LoggerInterface $logger, StreamxClientConfiguration $configuration) {
        $this->logger = $logger;
        $this->configuration = $configuration;
    }

    /**
     * @throws StreamxClientException
     */
    public function getClient(int $storeId): StreamxClient {
        $ingestionClient = $this->buildIngestionClient(
            $this->configuration->getIngestionBaseUrl($storeId),
            $this->configuration->getAuthToken($storeId),
            $this->configuration->shouldDisableCertificateValidation($storeId)
        );

        $publisher = $ingestionClient->newPublisher(
            $this->configuration->getChannelName($storeId),
            $this->configuration->getChannelSchemaName($storeId)
        );

        return new StreamxClient(
            $this->logger,
            $publisher,
            $this->configuration->getProductKeyPrefix($storeId),
            $this->configuration->getCategoryKeyPrefix($storeId)
        );
    }

    /**
     * @throws StreamxClientException
     */
    private function buildIngestionClient(string $ingestionBaseUrl, ?string $authToken, bool $shouldDisableCertificateValidation): IngestionClient {
        $builder = StreamxClientBuilders::create($ingestionBaseUrl);

        if (!empty($authToken)) {
            $builder->setAuthToken($authToken);
        }

        $httpClient = $this->prepareHttpClient($shouldDisableCertificateValidation);
        $builder->setHttpClient($httpClient);

        return $builder->build();
    }

    private function prepareHttpClient(bool $shouldDisableCertificateValidation): GuzzleHttpClient {
        $clientOptions = [
            'connect_timeout' => 1, // maximum time (in seconds) to establish the connection
            'timeout' => 5 // maximum time (in seconds) to wait for response
        ];

        if ($shouldDisableCertificateValidation) {
            $clientOptions[] = [
                'verify' => false
            ];
        }

        return new GuzzleHttpClient($clientOptions);
    }
}

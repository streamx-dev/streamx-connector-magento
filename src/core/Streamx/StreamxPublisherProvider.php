<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Streamx;

use GuzzleHttp\Client as GuzzleHttpClient;
use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Builders\StreamxClientBuilders;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use Streamx\Clients\Ingestion\Publisher\Publisher;
use Streamx\Clients\Ingestion\StreamxClient;

class StreamxPublisherProvider {

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    /**
     * @throws StreamxClientException
     */
    public function getStreamxPublisher(
        string $ingestionBaseUrl,
        string $channelName,
        string $channelSchemaName,
        ?string $authToken,
        bool $shouldDisableCertificateValidation
    ): Publisher {
        $this->logger->info("Creating new publisher for $ingestionBaseUrl / $channelName / $channelSchemaName");
        $ingestionClient = $this->buildStreamxClient($ingestionBaseUrl, $authToken, $shouldDisableCertificateValidation);
        return $ingestionClient->newPublisher($channelName, $channelSchemaName);
    }

    /**
     * @throws StreamxClientException
     */
    private function buildStreamxClient(string $ingestionBaseUrl, ?string $authToken, bool $shouldDisableCertificateValidation): StreamxClient {
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

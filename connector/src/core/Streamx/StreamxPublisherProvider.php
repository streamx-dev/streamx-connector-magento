<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Streamx;

use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Builders\StreamxClientBuilders;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use Streamx\Clients\Ingestion\Publisher\Publisher;
use Streamx\Clients\Ingestion\StreamxClient;
use StreamX\ConnectorCore\Api\Client\BuilderInterface;

class StreamxPublisherProvider {

    private LoggerInterface $logger;
    private Publisher $publisher;

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    /**
     * @throws StreamxClientException
     */
    public function buildStreamxPublisher(array $options) {
        if (isset($this->publisher)) {
            $this->logger->info("Reusing publisher");
            return $this->publisher;
        }

        $ingestionBaseUrl = $options[ClientConfiguration::INGESTION_BASE_URL_FIELD];
        $channelName = $options[ClientConfiguration::CHANNEL_NAME_FIELD];
        $channelSchemaName = $options[ClientConfiguration::CHANNEL_SCHEMA_NAME_FIELD];
        $authToken = $options[ClientConfiguration::AUTH_TOKEN_FIELD];

        $this->logger->info("Creating new publisher for $ingestionBaseUrl / $channelName / $channelSchemaName");
        $ingestionClient = $this->buildStreamxClient($ingestionBaseUrl, $authToken);
        $this->publisher = $ingestionClient->newPublisher($channelName, $channelSchemaName);

        return $this->publisher;
    }

    /**
     * @throws StreamxClientException
     */
    private function buildStreamxClient($ingestionBaseUrl, $authToken): StreamxClient {
        $builder = StreamxClientBuilders::create($ingestionBaseUrl);
        if (!empty($authToken)) {
            $builder->setAuthToken($authToken);
        }
        return $builder->build();
    }
}

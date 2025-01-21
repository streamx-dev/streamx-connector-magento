<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Streamx;

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
    public function getStreamxPublisher(string $ingestionBaseUrl, string $channelName, string $channelSchemaName, ?string $authToken): Publisher {
        $this->logger->info("Creating new publisher for $ingestionBaseUrl / $channelName / $channelSchemaName");
        $ingestionClient = $this->buildStreamxClient($ingestionBaseUrl, $authToken);
        return $ingestionClient->newPublisher($channelName, $channelSchemaName);
    }

    /**
     * @throws StreamxClientException
     */
    private function buildStreamxClient(string $ingestionBaseUrl, ?string $authToken): StreamxClient {
        $builder = StreamxClientBuilders::create($ingestionBaseUrl);
        if (!empty($authToken)) {
            $builder->setAuthToken($authToken);
        }
        return $builder->build();
    }
}

<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Streamx;

use StreamX\ConnectorCore\Api\Client\BuilderInterface;
use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Builders\StreamxClientBuilders;
use Streamx\Clients\Ingestion\Publisher\Publisher;

class StreamxPublisherProvider {

    private LoggerInterface $logger;
    private Publisher $publisher;

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    public function buildPublisher(array $options) {
        if (isset($this->publisher)) {
            $this->logger->info("Reusing publisher");
            return $this->publisher;
        }

        $this->logger->info("Creating new publisher with options: " . json_encode($options));

        $ingestionBaseUrl = $options[ClientConfiguration::INGESTION_BASE_URL_FIELD];
        $pagesChannelSchemaName = $options[ClientConfiguration::PAGES_SCHEMA_NAME_FIELD];

        $ingestionClient = StreamxClientBuilders::create($ingestionBaseUrl)->build();
        $this->publisher = $ingestionClient->newPublisher("pages", $pagesChannelSchemaName);

        return $this->publisher;
    }

}

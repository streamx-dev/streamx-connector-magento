<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client;

use Exception;
use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Publisher\Publisher;
use StreamX\ConnectorCore\Traits\ExceptionLogger;

class StreamxAvailabilityChecker {
    use ExceptionLogger;

    private LoggerInterface $logger;
    private Publisher $schemasFetcher;

    public function __construct(
        LoggerInterface $logger,
        StreamxClientConfiguration $configuration,
        int $storeId
    ) {
        $this->logger = $logger;
        $this->schemasFetcher = StreamxPublisherFactory::createStreamxPublisher($configuration, $storeId, false);
    }

    public function isStreamxAvailable(): bool {
        try {
            $schema = $this->schemasFetcher->fetchSchema();
            if (str_contains($schema, 'IngestionMessage')) {
                return true;
            }
            $this->logger->error("Requested StreamX channel is not available, Ingestion Message definition is missing in schema:\n$schema");
        } catch (Exception $e) {
            $this->logExceptionAsError('Exception checking if StreamX is available', $e);
        }
        return false;
    }
}
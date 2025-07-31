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
    private StreamxPublisherFactory $streamxPublisherFactory;

    public function __construct(
        LoggerInterface $logger,
        StreamxPublisherFactory $streamxPublisherFactory
    ) {
        $this->logger = $logger;
        $this->streamxPublisherFactory = $streamxPublisherFactory;
    }

    public function isStreamxAvailable(int $storeId): bool {
        try {
            $schemasFetcher = $this->streamxPublisherFactory->createStreamxPublisher($storeId, false)->getPublisher();
            $schema = $schemasFetcher->fetchSchema();
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
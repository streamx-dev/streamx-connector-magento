<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client;

use Exception;
use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Publisher\Message;
use Streamx\Clients\Ingestion\Publisher\Publisher;
use StreamX\ConnectorCore\Api\IngestionMessagesSender;
use StreamX\ConnectorCore\Traits\ExceptionLogger;

class StreamxIngestor implements IngestionMessagesSender {
    use ExceptionLogger;

    private LoggerInterface $logger;
    private StreamxClientConfiguration $clientConfiguration;
    private array $streamxPublishers = []; // by store ID

    public function __construct(LoggerInterface $logger, StreamxClientConfiguration $clientConfiguration) {
        $this->logger = $logger;
        $this->clientConfiguration = $clientConfiguration;
    }

    /**
     * @param Message[] $ingestionMessages
     * @return true if and only if all messages are successfully ingested by StreamX (false otherwise)
     */
    public function send(array $ingestionMessages, int $storeId): bool {
        $keys = array_column($ingestionMessages, 'key');
        $this->logger->info("Executing IngestionRequest for store $storeId with keys " . json_encode($keys));

        $streamxPublisher = $this->getOrCreateStreamxPublisher($storeId);
        return $this->doIngestMessages($streamxPublisher, $ingestionMessages);
    }

    private function getOrCreateStreamxPublisher(int $storeId): Publisher {
        if (!isset($this->streamxPublishers[$storeId])) {
            $this->streamxPublishers[$storeId] = StreamxPublisherFactory::createStreamxPublisher($this->clientConfiguration, $storeId, true);
        }
        return $this->streamxPublishers[$storeId];
    }

    private function doIngestMessages($streamxPublisher, array $ingestionMessages): bool {
        $success = true;
        try {
            $messageStatuses = $streamxPublisher->sendMulti($ingestionMessages);

            foreach ($messageStatuses as $messageStatus) {
                if ($messageStatus->getSuccess() === null) {
                    $success = false;
                    $this->logger->error('Ingestion failure: ' . json_encode($messageStatus->getFailure()));
                }
            }
        } catch (Exception $e) {
            $success = false;
            $this->logExceptionAsError('Ingestion exception', $e);
        }

        $this->logger->info("Finished executing ingestion request with result: $success");
        return $success;
    }
}

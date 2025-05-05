<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client;

use Exception;
use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use Streamx\Clients\Ingestion\Impl\MessageStatus;
use Streamx\Clients\Ingestion\Publisher\Message;
use Streamx\Clients\Ingestion\Publisher\Publisher;
use StreamX\ConnectorCore\Traits\ExceptionLogger;

class StreamxIngestor {
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
     * @return true if and only if all messages are successfully ingested to, and responded with success by StreamX (false otherwise)
     * @throws StreamxClientException
     */
    public function send(array $ingestionMessages, int $storeId): bool {
        $keys = array_column($ingestionMessages, 'key');
        $action = implode(', ', array_unique(array_column($ingestionMessages, 'action')));
        $this->logger->info("Executing IngestionRequest for store $storeId with action $action and keys " . json_encode($keys));

        $streamxPublisher = $this->getOrCreateStreamxPublisher($storeId);
        $messageStatuses = $streamxPublisher->sendMulti($ingestionMessages);

        $success = $this->isEachStatusSuccess($ingestionMessages, $messageStatuses);
        $this->logger->info("Finished executing ingestion request with result: $success");
        return $success;
    }

    private function getOrCreateStreamxPublisher(int $storeId): Publisher {
        if (!isset($this->streamxPublishers[$storeId])) {
            $this->streamxPublishers[$storeId] = StreamxPublisherFactory::createStreamxPublisher($this->clientConfiguration, $storeId, true);
        }
        return $this->streamxPublishers[$storeId];
    }

    /**
     * @param Message[] $inputMessages
     * @param MessageStatus[] $responses
     */
    private function isEachStatusSuccess(array $inputMessages, array $responses): bool {
        $inputMessagesCount = count($inputMessages);
        $responsesCount = count($responses);

        $sameNumberOfResponsesAndMessages = $responsesCount === $inputMessagesCount;
        if (!$sameNumberOfResponsesAndMessages) {
            $this->logger->warning("Received $responsesCount responses for $inputMessagesCount messages");
        }

        $success = true;
        for ($i = 0; $i < $responsesCount; $i++) {
            $response = $responses[$i];
            if ($response->getSuccess() === null) {
                $success = false;

                $errorMessage = 'Ingestion failure: ' . json_encode($response->getFailure());
                if ($sameNumberOfResponsesAndMessages) {
                    $inputMessage = $inputMessages[$i];
                    $errorMessage .= ". The failing message:\n" . json_encode($inputMessage);
                }
                $this->logger->error($errorMessage);
            }
        }
        return $success;
    }
}

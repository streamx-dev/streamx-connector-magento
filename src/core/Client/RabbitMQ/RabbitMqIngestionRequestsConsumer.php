<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client\RabbitMQ;

use Exception;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Publisher\Publisher;
use StreamX\ConnectorCore\Client\StreamxClientConfiguration;
use StreamX\ConnectorCore\Client\StreamxPublisherFactory;
use StreamX\ConnectorCore\Traits\ExceptionLogger;

/**
 * Consumes Ingestion Requests from Rabbit MQ queue and executes them
 */
class RabbitMqIngestionRequestsConsumer extends BaseRabbitMqIngestionRequestsService {
    use ExceptionLogger;

    private StreamxClientConfiguration $clientConfiguration;
    private array $streamxPublishers = []; // by store ID

    public function __construct(
        LoggerInterface $logger,
        RabbitMqConfiguration $rabbitMqConfiguration,
        StreamxClientConfiguration $clientConfiguration
    ) {
        parent::__construct($logger, $rabbitMqConfiguration);
        $this->clientConfiguration = $clientConfiguration;
    }

    public function startConsumingMessages(): void {
        // Prepare function that will handle consuming incoming messages
        $consumeMessageFunction = function (AMQPMessage $message) {
            $this->logger->info("Consuming message from $this->queueName");
            try {
                $ingestionRequest = IngestionRequest::fromJson($message->getBody());
                if ($this->ingest($ingestionRequest)) {
                    $message->ack();
                } else {
                    $message->nack(true); // TODO test if the message will actually be redelivered automatically
                }
            } catch (Exception $e) {
                $this->logExceptionAsError("Error processing message with body {$message->getBody()}", $e);
                $message->nack(true); // TODO test if the message will actually be redelivered automatically
            }
        };

        // Start the consumer for consuming messages from the queue
        $this->channel->basic_consume($this->queueName, '', false, false, false, false, $consumeMessageFunction);

        // Keep the channel ready to consume messages indefinitely
        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }

    private function ingest(IngestionRequest $ingestionRequest): bool {
        $ingestionMessages = $ingestionRequest->getIngestionMessages();
        $keys = array_column($ingestionMessages, 'key');
        $storeId = $ingestionRequest->getStoreId();
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

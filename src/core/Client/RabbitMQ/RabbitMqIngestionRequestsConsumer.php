<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client\RabbitMQ;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use StreamX\ConnectorCore\Client\StreamxIngestor;
use StreamX\ConnectorCore\Console\Command\OutputInterfaceLoggerWrapper;
use StreamX\ConnectorCore\Traits\ExceptionLogger;
use Throwable;

/**
 * Consumes Ingestion Requests from Rabbit MQ queue and executes them
 */
class RabbitMqIngestionRequestsConsumer {
    use ExceptionLogger;

    private const CONSUME_MESSAGES_BATCH_INTERVAL_MICROSECONDS = 100_000; // 100 millis
    private const CHECK_IF_RABBIT_MQ_IS_ENABLED_INTERVAL_SECONDS = 2;
    private const MAX_MESSAGES_IN_ONE_ITERATION = 100;

    private LoggerInterface $logger;
    private StreamxIngestor $streamxIngestor;
    private RabbitMqConfiguration $rabbitMqConfiguration;

    public function __construct(
        LoggerInterface $logger,
        StreamxIngestor $streamxIngestor,
        RabbitMqConfiguration $rabbitMqConfiguration
    ) {
        $this->logger = $logger;
        $this->streamxIngestor = $streamxIngestor;
        $this->rabbitMqConfiguration = $rabbitMqConfiguration;
    }

    public function start(OutputInterfaceLoggerWrapper $loggerWrapper): void {
        while (true) {
            if ($this->rabbitMqConfiguration->isEnabled()) {
                try {
                    $this->consumeMessages($loggerWrapper);
                } catch (Throwable $e) {
                    $this->logExceptionAsError('Error while consuming messages', $e);
                }
                usleep(self::CONSUME_MESSAGES_BATCH_INTERVAL_MICROSECONDS);
            } else {
                $loggerWrapper->info('Rabbit MQ is currently disabled in configuration');
                sleep(self::CHECK_IF_RABBIT_MQ_IS_ENABLED_INTERVAL_SECONDS);
            }
        }
    }

    private function consumeMessages(OutputInterfaceLoggerWrapper $loggerWrapper): void {
        $connection = RabbitMqManager::newConnection($this->rabbitMqConfiguration);
        $channel = RabbitMqManager::newChannel($connection);

        for ($i = 0; $i < self::MAX_MESSAGES_IN_ONE_ITERATION; $i++) {
            $message = $channel->basic_get(RabbitMqManager::QUEUE_NAME);
            if ($message) {
                $this->consumeMessage($message, $channel, $loggerWrapper);
            } else { // no more messages on the queue currently
                break;
            }
        }

        $connection->close(); // closes also its channel
    }

    private function consumeMessage(AMQPMessage $message, AMQPChannel $channel, OutputInterfaceLoggerWrapper $loggerWrapper): void {
        $ingestionKeys = RabbitMqManager::readIngestionKeys($message);
        $this->logger->info("Consuming message with ingestion keys $ingestionKeys");

        $messageBody = $message->getBody();
        $messageBodyBeginning = substr($messageBody, 0, 100) . ' (...)';

        try {
            $ingestionRequest = IngestionRequest::fromJson($messageBody);
        } catch (Throwable $e) {
            $this->logExceptionAsError("Error deserializing IngestionRequest from $messageBodyBeginning", $e);
            $this->transferToDeadLetterQueue($message, $channel, 'Invalid IngestionRequest JSON', $loggerWrapper);
            return;
        }

        try {
            $success = $this->sendToStreamX($ingestionRequest);
        } catch (StreamxClientException $e) {
            $this->logExceptionAsError("Potentially recoverable error sending the message to StreamX: $messageBodyBeginning", $e);
            $this->transferToRetryQueue($message, $channel, 'Error sending to StreamX: ' . $e->getMessage(), $loggerWrapper);
            return;
        } catch (Throwable $e) {
            $this->logExceptionAsError("Unexpected error sending the message to StreamX: $messageBodyBeginning", $e);
            $this->transferToDeadLetterQueue($message, $channel, 'Critical error sending to StreamX: ' . get_class($e), $loggerWrapper);
            return;
        }

        if (!$success) {
            $this->logger->error("Error response from StreamX to the message: $messageBodyBeginning");
            $this->transferToDeadLetterQueue($message, $channel, 'Error response from StreamX', $loggerWrapper);
            return;
        }

        $this->ackMessage($message, $channel);
    }

    /**
     * @throws StreamxClientException
     */
    private function sendToStreamX(IngestionRequest $ingestionRequest): bool {
        return $this->streamxIngestor->send(
            $ingestionRequest->getIngestionMessages(),
            $ingestionRequest->getStoreId()
        );
    }

    private function transferToDeadLetterQueue(AMQPMessage $message, AMQPChannel $channel, string $errorMessage, OutputInterfaceLoggerWrapper $loggerWrapper) {
        $loggerWrapper->error($errorMessage);
        $channel->basic_nack($message->getDeliveryTag()); // nacking with requeue=false will cause transferring the message to exchange configured in the 'x-dead-letter-exchange' property of the source queue
    }

    private function transferToRetryQueue(AMQPMessage $message, AMQPChannel $channel, string $errorMessage, OutputInterfaceLoggerWrapper $loggerWrapper) {
        $loggerWrapper->error($errorMessage);
        // TODO for now:
        $this->transferToDeadLetterQueue($message, $channel, $errorMessage, $loggerWrapper);
        // TODO instead, implement with back off mechanism: retry after 1s, then after 2s, then after 4s, 8s etc, 16, 32, give up after a day
    }

    private function ackMessage(AMQPMessage $message, AMQPChannel $channel) {
        $channel->basic_ack($message->getDeliveryTag());
    }
}

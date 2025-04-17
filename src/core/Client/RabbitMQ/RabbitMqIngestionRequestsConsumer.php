<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client\RabbitMQ;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use StreamX\ConnectorCore\Client\StreamxIngestor;
use StreamX\ConnectorCore\Traits\ExceptionLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Consumes Ingestion Requests from Rabbit MQ queue and executes them
 */
class RabbitMqIngestionRequestsConsumer extends BaseRabbitMqIngestionRequestsService {
    use ExceptionLogger;

    private const CONSUME_MESSAGES_BATCH_INTERVAL_MICROSECONDS = 100_000; // 100 millis
    private const CHECK_IF_RABBIT_MQ_IS_ENABLED_INTERVAL_SECONDS = 2;
    private const MAX_MESSAGES_IN_ONE_ITERATION = 100;

    private LoggerInterface $logger;
    private StreamxIngestor $streamxIngestor;

    public function __construct(
        RabbitMqConfiguration $rabbitMqConfiguration,
        LoggerInterface $logger,
        StreamxIngestor $streamxIngestor
    ) {
        parent::__construct($rabbitMqConfiguration);
        $this->logger = $logger;
        $this->streamxIngestor = $streamxIngestor;
    }

    public function start(OutputInterface $output): void {
        while (true) {
            if (parent::getRabbitMqConfiguration()->isEnabled()) {
                try {
                    $this->consumeMessages($output);
                } catch (Throwable $e) {
                    $this->logExceptionAsError('Error while consuming messages', $e);
                }
                usleep(self::CONSUME_MESSAGES_BATCH_INTERVAL_MICROSECONDS);
            } else {
                $output->writeln('Rabbit MQ is currently disabled in configuration');
                sleep(self::CHECK_IF_RABBIT_MQ_IS_ENABLED_INTERVAL_SECONDS);
            }
        }
    }

    private function consumeMessages(OutputInterface $output): void {
        $connection = parent::newConnection();
        $channel = parent::newChannel($connection);

        for ($i = 0; $i < self::MAX_MESSAGES_IN_ONE_ITERATION; $i++) {
            $message = $channel->basic_get(parent::QUEUE_NAME);
            if ($message) {
                $this->consumeMessage($message, $channel, $output);
            } else { // no more messages on the queue currently
                break;
            }
        }

        $connection->close(); // closes also its channel
    }

    private function consumeMessage(AMQPMessage $message, AMQPChannel $channel, OutputInterface $output): void {
        $ingestionKeys = $this->readIngestionKeys($message);
        $this->logger->info("Consuming message with ingestion keys $ingestionKeys");

        $messageBody = $message->getBody();
        $messageBodyBeginning = substr($messageBody, 0, 100) . ' (...)';

        try {
            $ingestionRequest = IngestionRequest::fromJson($messageBody);
        } catch (Throwable $e) {
            $this->logExceptionAsError("Error deserializing IngestionRequest from $messageBodyBeginning", $e);
            $this->transferToDeadLetterQueue($message, $channel, 'Invalid IngestionRequest JSON', $output);
            return;
        }

        try {
            $success = $this->sendToStreamX($ingestionRequest);
        } catch (StreamxClientException $e) {
            $this->logExceptionAsError("Potentially recoverable error sending the message to StreamX: $messageBodyBeginning", $e);
            $this->transferToRetryQueue($message, $channel, 'Error sending to StreamX: ' . $e->getMessage(), $output);
            return;
        } catch (Throwable $e) {
            $this->logExceptionAsError("Unexpected error sending the message to StreamX: $messageBodyBeginning", $e);
            $this->transferToDeadLetterQueue($message, $channel, 'Critical error sending to StreamX: ' . get_class($e), $output);
            return;
        }

        if (!$success) {
            $this->logger->error("Error response from StreamX to the message: $messageBodyBeginning");
            $this->transferToDeadLetterQueue($message, $channel, 'Error response from StreamX', $output);
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

    private function transferToDeadLetterQueue(AMQPMessage $message, AMQPChannel $channel, string $errorMessage, OutputInterface $output) {
        $output->writeln("<error>$errorMessage</error>");
        $channel->basic_nack($message->getDeliveryTag()); // nacking with requeue=false will cause transferring the message to exchange configured in the 'x-dead-letter-exchange' property of the source queue
    }

    private function transferToRetryQueue(AMQPMessage $message, AMQPChannel $channel, string $errorMessage, OutputInterface $output) {
        $output->writeln("<error>$errorMessage</error>");
        // TODO for now:
        $this->transferToDeadLetterQueue($message, $channel, $errorMessage, $output);
        // TODO implement with back mechanism: retry after 1s, then after 2s, then after 4s, 8s etc, 16, 32, give up after a day
    }

    private function ackMessage(AMQPMessage $message, AMQPChannel $channel) {
        $channel->basic_ack($message->getDeliveryTag());
    }
}

<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client\RabbitMQ;

use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use StreamX\ConnectorCore\Client\StreamxIngestor;
use StreamX\ConnectorCore\Traits\ExceptionLogger;

/**
 * Consumes Ingestion Requests from Rabbit MQ queue and executes them
 */
class RabbitMqIngestionRequestsConsumer extends BaseRabbitMqIngestionRequestsService {
    use ExceptionLogger;

    private LoggerInterface $logger;
    private StreamxIngestor $streamxIngestor;
    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel $channel = null;

    public function __construct(
        RabbitMqConfiguration $rabbitMqConfiguration,
        LoggerInterface $logger,
        StreamxIngestor $streamxIngestor
    ) {
        parent::__construct($rabbitMqConfiguration);
        $this->logger = $logger;
        $this->streamxIngestor = $streamxIngestor;
    }

    public function startConsumingMessages(): void {
        $this->connection = parent::newConnection();
        $this->channel = parent::newChannel($this->connection);

        $consumeMessageFunction = function (AMQPMessage $message) {
            $this->consumeMessage($message);
        };

        // start the consumer for consuming messages from the queue
        $this->channel->basic_consume($this->getQueueName(), '', false, false, false, false, $consumeMessageFunction);

        // keep the channel ready to consume messages indefinitely
        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }

    private function consumeMessage(AMQPMessage $message): void {
        $this->logger->info("Consuming message from {$this->getQueueName()}");
        try {
            $ingestionRequest = IngestionRequest::fromJson($message->getBody());
            if ($this->sendToStreamX($ingestionRequest)) {
                $message->ack();
            } else {
                $message->nack(true); // TODO verify if the message will actually be redelivered automatically
            }
        } catch (Exception $e) {
            $this->logExceptionAsError("Error processing message with body {$message->getBody()}", $e);
            $message->nack(true); // TODO verify if the message will actually be redelivered automatically
        }
    }

    private function sendToStreamX(IngestionRequest $ingestionRequest): bool {
        return $this->streamxIngestor->send(
            $ingestionRequest->getIngestionMessages(),
            $ingestionRequest->getStoreId()
        );
    }

    public function __destruct() {
        if ($this->connection) {
            $this->connection->close(); // internally closes also its channels
        }
    }
}

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
        $this->channel->basic_consume(parent::queueName, '', false, false, false, false, $consumeMessageFunction);

        // keep the channel ready to consume messages indefinitely
        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }

    private function consumeMessage(AMQPMessage $message): void {
        $this->logger->info('Consuming message from ' . parent::queueName);
        $messageBody = $message->getBody();

        try {
            $ingestionRequest = IngestionRequest::fromJson($messageBody);
        } catch (Exception $e) {
            $this->logExceptionAsError("Error deserializing IngestionRequest from the following RabbitMQ message, giving up\n$messageBody", $e);
            $message->nack(false); // remove the message from queue, since it's broken there is no use re-queueing it
            return;
        }

        try {
            $success = $this->sendToStreamX($ingestionRequest);
        } catch (Exception $e) {
            $this->logExceptionAsError("Error sending the following message to StreamX, re-queueing\n$messageBody", $e);
            $message->nack(true); // keep the message on queue and keep resending it until it's delivered to StreamX
            return;
        }

        if (!$success) {
            $this->logger->error("Error response from StreamX to the following message, giving up\n$messageBody");
            $message->nack(false); // remove the message from queue, since StreamX will probably not accept it in next attempts, so there is no use re-queueing it
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

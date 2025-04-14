<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client\RabbitMQ;

use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;

abstract class BaseRabbitMqIngestionRequestsService {

    private static bool $isQueueInitialized = false;

    private string $exchange;
    private string $queueName;
    private string $routingKey;

    private string $dlqExchange;
    private string $dlqQueueName;
    private string $dlqRoutingKey;

    private string $host;
    private int $port;
    private string $user;
    private string $password;

    protected function __construct(RabbitMqConfiguration $configuration) {
        $this->exchange = 'streamx';
        $this->queueName = 'ingestion-requests';
        $this->routingKey = "$this->queueName.*";

        $this->dlqExchange = 'streamx-dlq';
        $this->dlqQueueName = 'ingestion-requests-dlq';
        $this->dlqRoutingKey = "$this->dlqQueueName-key";

        $this->host = $configuration->getHost();
        $this->port = $configuration->getPort();
        $this->user = $configuration->getUser();
        $this->password = $configuration->getPassword();
    }

    protected function getQueueName(): string {
        return $this->queueName;
    }

    protected function newConnection(): AMQPStreamConnection {
        return new AMQPStreamConnection($this->host, $this->port, $this->user, $this->password);
    }

    protected function newChannel(AMQPStreamConnection $connection): AMQPChannel {
        $channel = $connection->channel();
        $this->initializeQueues($channel);
        return $channel;
    }

    /**
     * Sends the message in new connection, and then closes the connection
     */
    protected function sendMessage(AMQPMessage $message): void {
        $connection = $this->newConnection();
        $channel = $connection->channel();
        $this->initializeQueues($channel);
        $channel->basic_publish($message, $this->exchange, $this->routingKey);
        $connection->close();
    }

    private function initializeQueues(AMQPChannel $channel): void {
        if (!self::$isQueueInitialized) {
            // create Dead Letter Queue (for nack'ed messages)
            $channel->exchange_declare($this->dlqExchange, 'direct', false, true, false);
            $channel->queue_declare($this->dlqQueueName, false, true, false, false);
            $channel->queue_bind($this->dlqQueueName, $this->dlqExchange, $this->dlqRoutingKey);

            // create the main queue
            $channel->exchange_declare($this->exchange, 'topic', false, true, false);
            $channel->queue_declare($this->queueName, false, true, false, false, false, [
                'x-dead-letter-exchange'    => ['S', $this->dlqExchange],
                'x-dead-letter-routing-key' => ['S', $this->dlqRoutingKey]
            ]);
            $channel->queue_bind($this->queueName, $this->exchange, $this->routingKey);

            self::$isQueueInitialized = true;
        }
    }
}

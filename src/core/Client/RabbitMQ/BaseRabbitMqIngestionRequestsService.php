<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client\RabbitMQ;

use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;

abstract class BaseRabbitMqIngestionRequestsService {

    private static bool $isQueueInitialized = false;

    public const exchange = 'streamx';
    public const queueName = 'ingestion-requests';
    public const routingKey = "ingestion-requests.*";

    public const dlqExchange = 'streamx-dlq';
    public const dlqQueueName = 'ingestion-requests-dlq';
    public const dlqRoutingKey = "ingestion-requests-dlq-key";

    private string $host;
    private int $port;
    private string $user;
    private string $password;

    protected function __construct(RabbitMqConfiguration $configuration) {
        $this->host = $configuration->getHost();
        $this->port = $configuration->getPort();
        $this->user = $configuration->getUser();
        $this->password = $configuration->getPassword();
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
        $channel->basic_publish($message, self::exchange, self::routingKey);
        $connection->close();
    }

    private function initializeQueues(AMQPChannel $channel): void {
        if (!self::$isQueueInitialized) {
            // create Dead Letter Queue (for nack'ed messages)
            $channel->exchange_declare(self::dlqExchange, 'direct', false, true, false);
            $channel->queue_declare(self::dlqQueueName, false, true, false, false);
            $channel->queue_bind(self::dlqQueueName, self::dlqExchange, self::dlqRoutingKey);

            // create the main queue
            $channel->exchange_declare(self::exchange, 'topic', false, true, false);
            $channel->queue_declare(self::queueName, false, true, false, false, false, [
                'x-dead-letter-exchange'    => ['S', self::dlqExchange],
                'x-dead-letter-routing-key' => ['S', self::dlqRoutingKey]
            ]);
            $channel->queue_bind(self::queueName, self::exchange, self::routingKey);

            self::$isQueueInitialized = true;
        }
    }
}

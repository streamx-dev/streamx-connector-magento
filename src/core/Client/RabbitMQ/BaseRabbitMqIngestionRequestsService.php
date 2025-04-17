<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client\RabbitMQ;

use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;

abstract class BaseRabbitMqIngestionRequestsService {

    private static bool $areQueuesInitialized = false;

    public const EXCHANGE = 'streamx';
    public const QUEUE_NAME = 'ingestion-requests';
    public const ROUTING_KEY = "ingestion-requests.*";

    public const DLQ_EXCHANGE = 'streamx-dlq';
    public const DLQ_QUEUE_NAME = 'ingestion-requests-dlq';
    public const DLQ_ROUTING_KEY = "ingestion-requests-dlq-key";

    private const APPLICATION_HEADERS_PROPERTY = 'application_headers';
    private const INGESTION_KEYS_HEADER = 'ingestion_keys';

    private RabbitMqConfiguration $rabbitMqConfiguration;
    private string $host;
    private int $port;
    private string $user;
    private string $password;

    protected function __construct(RabbitMqConfiguration $rabbitMqConfiguration) {
        $this->rabbitMqConfiguration = $rabbitMqConfiguration;
        $this->host = $rabbitMqConfiguration->getHost();
        $this->port = $rabbitMqConfiguration->getPort();
        $this->user = $rabbitMqConfiguration->getUser();
        $this->password = $rabbitMqConfiguration->getPassword();
    }

    protected function getRabbitMqConfiguration(): RabbitMqConfiguration {
        return $this->rabbitMqConfiguration;
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
        $channel->basic_publish($message, self::EXCHANGE, self::ROUTING_KEY);
        $connection->close();
    }

    private function initializeQueues(AMQPChannel $channel): void {
        if (!self::$areQueuesInitialized) {
            // create Dead Letter Queue (for nack'ed messages)
            $channel->exchange_declare(self::DLQ_EXCHANGE, 'direct', false, true, false);
            $channel->queue_declare(self::DLQ_QUEUE_NAME, false, true, false, false);
            $channel->queue_bind(self::DLQ_QUEUE_NAME, self::DLQ_EXCHANGE, self::DLQ_ROUTING_KEY);

            // create the main queue
            $channel->exchange_declare(self::EXCHANGE, 'topic', false, true, false);
            $channel->queue_declare(self::QUEUE_NAME, false, true, false, false, false, [
                'x-dead-letter-exchange'    => ['S', self::DLQ_EXCHANGE],
                'x-dead-letter-routing-key' => ['S', self::DLQ_ROUTING_KEY]
            ]);
            $channel->queue_bind(self::QUEUE_NAME, self::EXCHANGE, self::ROUTING_KEY);

            self::$areQueuesInitialized = true;
        }
    }

    public static function createRabbitMqMessage(string $body, string $ingestionKeys): AMQPMessage {
        return new AMQPMessage(
            $body,
            [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                self::APPLICATION_HEADERS_PROPERTY => new AMQPTable([
                    self::INGESTION_KEYS_HEADER => $ingestionKeys
                ])
            ]
        );
    }

    public static function readIngestionKeys(AMQPMessage $message): string {
        /** @var $headers AMQPTable */
        $headers = $message->get_properties()[self::APPLICATION_HEADERS_PROPERTY] ?? null;
        if ($headers) {
            return $headers->getNativeData()[self::INGESTION_KEYS_HEADER] ?? 'undefined';
        }
        return 'undefined';
    }
}

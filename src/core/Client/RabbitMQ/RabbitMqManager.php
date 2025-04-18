<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client\RabbitMQ;

use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitMqManager {

    private static bool $areQueuesInitialized = false;

    public const EXCHANGE = 'streamx';
    public const QUEUE_NAME = 'ingestion-requests';
    public const ROUTING_KEY = "ingestion-requests.*";

    public const DLQ_EXCHANGE = 'streamx-dlq';
    public const DLQ_QUEUE_NAME = 'ingestion-requests-dlq';
    public const DLQ_ROUTING_KEY = "ingestion-requests-dlq-key";

    private const APPLICATION_HEADERS_PROPERTY = 'application_headers';
    private const INGESTION_KEYS_HEADER = 'ingestion_keys';

    private function __construct() {
        // no instances
    }

    public static function newConnection(RabbitMqConfiguration $rabbitMqConfiguration): AMQPStreamConnection {
        return new AMQPStreamConnection(
            $rabbitMqConfiguration->getHost(),
            $rabbitMqConfiguration->getPort(),
            $rabbitMqConfiguration->getUser(),
            $rabbitMqConfiguration->getPassword()
        );
    }

    public static function newChannel(AMQPStreamConnection $connection): AMQPChannel {
        $channel = $connection->channel();
        self::initializeQueues($channel);
        return $channel;
    }

    /**
     * Sends the message in new connection, and then closes the connection
     */
    public static function sendMessage(RabbitMqConfiguration $rabbitMqConfiguration, AMQPMessage $message): void {
        $connection = self::newConnection($rabbitMqConfiguration);
        $channel = self::newChannel($connection);
        $channel->basic_publish($message, self::EXCHANGE, self::ROUTING_KEY);
        $connection->close();
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

    private static function initializeQueues(AMQPChannel $channel): void {
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
}

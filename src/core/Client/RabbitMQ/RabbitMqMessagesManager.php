<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client\RabbitMQ;

use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use StreamX\ConnectorCatalog\test\integration\BaseStreamxTest;

class RabbitMqMessagesManager {

    private function __construct() {
        // no instances
    }

    public static function doWithChannel(RabbitMqConfiguration $rabbitMqConfiguration, callable $function) {
        $connection = new AMQPStreamConnection(
            $rabbitMqConfiguration->getHost(),
            $rabbitMqConfiguration->getPort(),
            $rabbitMqConfiguration->getUser(),
            $rabbitMqConfiguration->getPassword()
        );
        $channel = $connection->channel();
        RabbitMqQueuesManager::ensureQueuesCreated($channel);

        try {
            $function($channel);
        } finally {
            $connection->close(); // closes also its channel
        }
    }

    /**
     * Sends the message in new connection, and then closes the connection
     */
    public static function sendIngestionRequestMessage(RabbitMqConfiguration $rabbitMqConfiguration, AMQPMessage $message): void {
        self::doWithChannel($rabbitMqConfiguration, fn(AMQPChannel $channel) =>
            $channel->basic_publish($message, RabbitMqQueuesManager::MAIN_EXCHANGE, RabbitMqQueuesManager::MAIN_QUEUE_ROUTING_KEY)
        );
    }

    public static function createIngestionRequestMessage(string $body, string $ingestionKeys): AMQPMessage {
        $properties = [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'application_headers' => new AMQPTable([
                'ingestion_keys' => $ingestionKeys
            ])
        ];
        return new AMQPMessage($body, $properties);
    }

    public static function readIngestionKeys(AMQPMessage $message): string {
        return self::readHeader($message, 'ingestion_keys', 'undefined');
    }

    public static function readRetryCount(AMQPMessage $message): int {
        return self::readHeader($message, 'x-retry-count', 0);
    }

    private static function readHeader(AMQPMessage $message, string $headerName, $fallbackValue) {
        /** @var $headers ?AMQPTable */
        $headers = $message->get_properties()['application_headers'] ?? null;
        if ($headers) {
            return $headers->getNativeData()[$headerName] ?? $fallbackValue;
        }
        return $fallbackValue;
    }

    public static function increaseRetryCount(AMQPMessage $message): int {
        /** @var $headers ?AMQPTable */
        $headers = $message->get_properties()['application_headers'];
        if (!$headers) {
            $headers = new AMQPTable();
            $message->set('application_headers', $headers);
        }
        $increasedRetryCount = 1 + self::readRetryCount($message);
        $headers->set('x-retry-count', $increasedRetryCount);
        return $increasedRetryCount;
    }
}

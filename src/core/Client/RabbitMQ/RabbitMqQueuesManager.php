<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client\RabbitMQ;

use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * Declares main queue for Ingestion Requests, and retry queues for retrying processing failed messages. <br />
 * When a message is retried for the first time, its redelivery time will be 1s. <br />
 * The retry time will grow exponentially (the configuration introduces exponential backoff retry mechanism). <br />
 * In general, when a message is retried for the Nth time, its redelivery time will be 2 ^ (N-1). <br />
 * This table shows the redelivery delays: <br />
 *
 *  | Retry # | Delay            |
 *  |--------:|------------------|
 *  |       1 | 0 d, 00:00:01    |
 *  |       2 | 0 d, 00:00:02    |
 *  |       3 | 0 d, 00:00:04    |
 *  |       4 | 0 d, 00:00:08    |
 *  |       5 | 0 d, 00:00:16    |
 *  |       6 | 0 d, 00:00:32    |
 *  |       7 | 0 d, 00:01:04    |
 *  |       8 | 0 d, 00:02:08    |
 *  |       9 | 0 d, 00:04:16    |
 *  |      10 | 0 d, 00:08:32    |
 *  |      11 | 0 d, 00:17:04    |
 *  |      12 | 0 d, 00:34:08    |
 *  |      13 | 0 d, 01:08:16    |
 *  |      14 | 0 d, 02:16:32    |
 *  |      15 | 0 d, 04:33:04    |
 *  |      16 | 0 d, 09:06:08    |
 *  |      17 | 0 d, 18:12:16    |
 *  |      18 | 1 d, 12:24:32    |
 *  |      19 | 3 d, 00:49:04    |
 *  |      20 | 6 d, 01:38:08    |
 *
 * After the message reaches max retries count (20), it will be permanently moved to a Dead Letter Queue
 */
class RabbitMqQueuesManager {

    private static bool $areQueuesCreated = false;

    private const EXCHANGE_TYPE = 'direct';

    public const MAIN_EXCHANGE = 'streamx';
    public const MAIN_QUEUE = 'ingestion-requests';
    public const MAIN_QUEUE_ROUTING_KEY = self::MAIN_QUEUE;

    public const MAX_RETRIES_COUNT = 20;
    private const INITIAL_RETRY_DELAY_SECONDS = 1;
    private const RETRY_DELAY_INCREASE_FACTOR = 2;

    public const RETRY_EXCHANGE = 'streamx-retry';
    private const RETRY_QUEUE_TEMPLATE = 'ingestion-requests-retry-%d';
    private const RETRY_QUEUE_ROUTING_KEY_TEMPLATE = self::RETRY_QUEUE_TEMPLATE;

    public const DEAD_LETTER_EXCHANGE = 'streamx-dead-letter';
    public const DEAD_LETTER_QUEUE = 'ingestion-requests-dead-letter';
    public const DEAD_LETTER_ROUTING_KEY = self::DEAD_LETTER_QUEUE;

    private function __construct() {
        // no instances
    }

    public static function ensureQueuesCreated(AMQPChannel $channel): void {
        if (!self::$areQueuesCreated) {
            self::createMainExchangeAndQueue($channel);
            self::createRetryExchangeAndQueues($channel);
            self::createDeadLetterExchangeAndQueue($channel);
            self::$areQueuesCreated = true;
        }
    }

    private static function createMainExchangeAndQueue(AMQPChannel $channel): void {
        self::declareExchange($channel, self::MAIN_EXCHANGE);
        self::declareQueue($channel, self::MAIN_QUEUE, self::MAIN_EXCHANGE, self::MAIN_QUEUE_ROUTING_KEY);
    }

    private static function createRetryExchangeAndQueues(AMQPChannel $channel): void {
        self::declareExchange($channel, self::RETRY_EXCHANGE);

        $timeToLiveMillis = self::INITIAL_RETRY_DELAY_SECONDS * 1000;
        for ($retryQueueNumber = 1; $retryQueueNumber <= self::MAX_RETRIES_COUNT; $retryQueueNumber++) {
            $retryQueue = self::getRetryQueueName($retryQueueNumber);
            $routingKey = self::getRetryQueueRoutingKey($retryQueueNumber);
            self::declareQueue($channel, $retryQueue, self::RETRY_EXCHANGE, $routingKey, [
                // once the time to live has elapsed, the message will be published back to the main queue, so processing retry can take place
                'x-message-ttl' => ['I', $timeToLiveMillis],
                'x-dead-letter-exchange' => ['S', self::MAIN_EXCHANGE],
                'x-dead-letter-routing-key' => ['S', self::MAIN_QUEUE_ROUTING_KEY]
            ]);

            $timeToLiveMillis *= self::RETRY_DELAY_INCREASE_FACTOR;
        }
    }

    private static function createDeadLetterExchangeAndQueue(AMQPChannel $channel): void {
        self::declareExchange($channel, self::DEAD_LETTER_EXCHANGE);
        self::declareQueue($channel, self::DEAD_LETTER_QUEUE, self::DEAD_LETTER_EXCHANGE, self::DEAD_LETTER_ROUTING_KEY);
    }

    private static function declareExchange(AMQPChannel $channel, string $name): void {
        $channel->exchange_declare($name, self::EXCHANGE_TYPE, false, true, false);
    }

    private static function declareQueue(AMQPChannel $channel, string $queue, string $exchange, string $routingKey, array $arguments = []): void {
        $channel->queue_declare($queue, false, true, false, false, false, $arguments);
        $channel->queue_bind($queue, $exchange, $routingKey);
    }

    public static function getRetryQueueName(int $retryCount): string {
        return sprintf(self::RETRY_QUEUE_TEMPLATE, $retryCount);
    }

    public static function getRetryQueueRoutingKey(int $retryCount): string {
        return sprintf(self::RETRY_QUEUE_ROUTING_KEY_TEMPLATE, $retryCount);
    }
}

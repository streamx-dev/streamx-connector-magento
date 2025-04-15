<?php

namespace StreamX\ConnectorCatalog\test\integration;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\AssertionFailedError;
use RuntimeException;
use StreamX\ConnectorCore\Client\RabbitMQ\BaseRabbitMqIngestionRequestsService as Rabbit;

class RabbitMqDeadLetterQueueTest extends BaseStreamxTest {

    protected function setUp(): void {
        self::clearQueues();
    }

    protected function tearDown(): void {
        self::clearQueues();
    }

    /** @test */
    public function shouldRedirectInvalidMessageToDeadLetterQueue() {
        // given
        $rabbitMqMessage = new AMQPMessage('This is not an Ingestion Request JSON');

        // when
        self::sendMessage($rabbitMqMessage);

        // then
        self::waitUntil(function () {
            self::assertMessagesCount(Rabbit::queueName, 0);
            self::assertMessagesCount(Rabbit::dlqQueueName, 1);
        });
    }

    private static function sendMessage(AMQPMessage $message): void {
        self::doWithChannel(function (AMQPChannel $channel) use ($message) {
            $channel->basic_publish($message, Rabbit::exchange, Rabbit::routingKey);
        });
    }

    private static function assertMessagesCount(string $queue, int $expectedCount): void {
        self::doWithChannel(function (AMQPChannel $channel) use ($queue, $expectedCount) {
            $queueInfo = $channel->queue_declare(
                $queue,
                true, // passive = true: just check, don't create
                true,
                false,
                false
            );
            $messageCount = $queueInfo[1];

            self::assertEquals($expectedCount, $messageCount);
        });
    }

    private static function doWithChannel(callable $function): void {
        $connection = new AMQPStreamConnection(
            BaseStreamxTest::RABBIT_MQ_HOST,
            BaseStreamxTest::RABBIT_MQ_PORT,
            BaseStreamxTest::RABBIT_MQ_USER,
            BaseStreamxTest::RABBIT_MQ_PASSWORD
        );
        $channel = $connection->channel();
        $function($channel);
        $connection->close();
    }

    private function waitUntil(callable $assertion, int $timeoutSeconds = 5, int $intervalMilliseconds = 100): void {
        $startTime = microtime(true);
        $lastException = null;

        while ((microtime(true) - $startTime) < $timeoutSeconds) {
            try {
                $assertion();
                return; // success, exit
            } catch (AssertionFailedError $e) {
                $lastException = $e;
                usleep($intervalMilliseconds * 1000); // wait before retrying
            }
        }

        // if we ran out of time, rethrow last exception
        if ($lastException !== null) {
            throw $lastException;
        }

        throw new RuntimeException("waitUntil timed out but no assertion was triggered");
    }

    private static function clearQueues(): void {
        self::doWithChannel(function (AMQPChannel $channel) {
            $channel->queue_purge(Rabbit::queueName);
            $channel->queue_purge(Rabbit::dlqQueueName);
        });
    }

}
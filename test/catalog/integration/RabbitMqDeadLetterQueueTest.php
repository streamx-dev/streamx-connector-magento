<?php

namespace StreamX\ConnectorCatalog\test\integration;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class RabbitMqDeadLetterQueueTest extends TestCase {

    /** @test */
    public function shouldPutInvalidMessageToDeadLetterQueue() {
        // given
        $this->purgeMessages('ingestion-requests');
        $this->purgeMessages('ingestion-requests-dlq');
        $rabbitMqMessage = new AMQPMessage('This is not an Ingestion Request JSON');

        // when
        $this->sendMessage($rabbitMqMessage);

        // then
        $this->waitUntil(function() {
            $this->assertMessagesCount('ingestion-requests', 0);
            $this->assertMessagesCount('ingestion-requests-dlq', 1);
        });
    }

    private function sendMessage(AMQPMessage $message): void {
        // TODO deduplicate, use constants, transform to integration test
        $connection = new AMQPStreamConnection('localhost', 5672, 'magento', 'magento');
        $channel = $connection->channel();
        $channel->basic_publish($message, 'streamx', 'ingestion-requests.*');
        $connection->close();
    }

    private function purgeMessages(string $queue): void {
        $connection = new AMQPStreamConnection('localhost', 5672, 'magento', 'magento');
        $channel = $connection->channel();
        $channel->queue_purge($queue);
        $connection->close();
    }

    private function assertMessagesCount(string $queue, int $expectedCount): void {
        $connection = new AMQPStreamConnection('localhost', 5672, 'magento', 'magento');
        $channel = $connection->channel();

        list($queue, $messageCount, $consumerCount) = $channel->queue_declare(
            $queue,
            true,   // passive = true: just check, don't create
            true,   // durable
            false,  // exclusive
            false   // auto-delete
        );

        $this->assertEquals($expectedCount, $messageCount);
        $channel->close();
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

        // If we ran out of time, rethrow last exception
        if ($lastException !== null) {
            throw $lastException;
        }

        throw new RuntimeException("waitUntil timed out but no assertion was triggered");
    }
}
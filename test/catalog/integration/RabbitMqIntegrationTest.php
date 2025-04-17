<?php

namespace StreamX\ConnectorCatalog\test\integration;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\AssertionFailedError;
use RuntimeException;
use Streamx\Clients\Ingestion\Publisher\Message;
use StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests\BaseAppEntityUpdateTest;
use StreamX\ConnectorCore\Client\Model\Data;
use StreamX\ConnectorCore\Client\RabbitMQ\BaseRabbitMqIngestionRequestsService as Rabbit;
use StreamX\ConnectorCore\Client\RabbitMQ\IngestionRequest;
use StreamX\ConnectorCore\Client\RabbitMQ\RabbitMqIngestionRequestsSender;

/**
 * @inheritdoc
 * @UsesProductIndexer
 */
class RabbitMqIntegrationTest extends BaseAppEntityUpdateTest {

    private const ingestionKey = 'key-1';
    private RabbitMqIngestionRequestsSender $rabbitMqSender;

    protected function setUp(): void {
        parent::setUp();
        $this->rabbitMqSender = $this->createRabbitMqSender();
        self::removeFromStreamX(self::ingestionKey);
    }

    private function createRabbitMqSender(): RabbitMqIngestionRequestsSender {
        $rabbitMqConfiguration = parent::createRabbitMqConfigurationMock();
        $logger = parent::createLoggerMock();
        return new RabbitMqIngestionRequestsSender($rabbitMqConfiguration, $logger);
    }

    /** @test */
    public function validIngestionMessage_ShouldBeProcessed() {
        // given
        $productJson = self::readValidationFileContent('original-bag-product.json');
        $validIngestionMessage = Message::newPublishMessage(self::ingestionKey, new Data($productJson))->build();
        $messagesCountBefore = self::getMessagesCount(Rabbit::QUEUE_NAME);
        $dlqMessagesCountBefore = self::getMessagesCount(Rabbit::DLQ_QUEUE_NAME);

        // when
        $this->rabbitMqSender->send(new IngestionRequest([$validIngestionMessage], parent::$store1Id));

        // then
        parent::assertExactDataIsPublished(self::ingestionKey, 'original-bag-product.json');

        // and: expecting none of the new messages to remain on queues
        self::assertMessagesCount(Rabbit::QUEUE_NAME, $messagesCountBefore);
        self::assertMessagesCount(Rabbit::DLQ_QUEUE_NAME, $dlqMessagesCountBefore);
    }

    /** @test */
    public function messageRejectedByStreamX_ShouldBeRedirectedToDeadLetterQueue() {
        // given
        $invalidIngestionMessage = new Message(
            self::ingestionKey,
            'unsupported-action', // currently this action is not supported by StreamX, so it returns error response
            null,
            (object)[],
            new Data(self::readValidationFileContent('original-bag-product.json'))
        );
        $messagesCountBefore = self::getMessagesCount(Rabbit::QUEUE_NAME);
        $dlqMessagesCountBefore = self::getMessagesCount(Rabbit::DLQ_QUEUE_NAME);

        // when
        $this->rabbitMqSender->send(new IngestionRequest([$invalidIngestionMessage], parent::$store1Id));

        // then
        parent::assertDataIsNotPublished(self::ingestionKey);

        // and: expecting the message to land on the Dead Letter Queue
        self::assertMessagesCount(Rabbit::QUEUE_NAME, $messagesCountBefore);
        self::assertMessagesCount(Rabbit::DLQ_QUEUE_NAME, 1 + $dlqMessagesCountBefore);

        // cleanup - remove the DLQ message from queue
        $this->removeMessageByContentPartValidatingIngestionKeys(
            Rabbit::DLQ_QUEUE_NAME,
            'unsupported-action',
            '["' . self::ingestionKey . '"]'
        );
    }

    /** @test */
    public function invalidMessage_ShouldBeRedirectedToDeadLetterQueue() {
        // given
        $rabbitMqMessage = new AMQPMessage('This is not an Ingestion Request JSON');
        $messagesCountBefore = self::getMessagesCount(Rabbit::QUEUE_NAME);
        $dlqMessagesCountBefore = self::getMessagesCount(Rabbit::DLQ_QUEUE_NAME);

        // when
        self::sendMessage($rabbitMqMessage);

        // then: expecting the message to land on the Dead Letter Queue
        self::assertMessagesCount(Rabbit::QUEUE_NAME, $messagesCountBefore);
        self::assertMessagesCount(Rabbit::DLQ_QUEUE_NAME, 1 + $dlqMessagesCountBefore);

        // cleanup - remove the DLQ message from queue
        $this->removeMessageByContentPartValidatingIngestionKeys(
            Rabbit::DLQ_QUEUE_NAME,
            'This is not an Ingestion Request JSON',
            'undefined'
        );
    }

    private static function sendMessage(AMQPMessage $message): void {
        self::doWithChannel(function (AMQPChannel $channel) use ($message) {
            $channel->basic_publish($message, Rabbit::EXCHANGE, Rabbit::ROUTING_KEY);
        });
    }

    private static function assertMessagesCount(string $queue, int $expectedCount): void {
        self::waitUntil(function () use ($queue, $expectedCount) {
            $actualCount = self::getMessagesCount($queue);
            self::assertEquals($expectedCount, $actualCount);
        });
    }

    private static function getMessagesCount(string $queue): int {
        return self::doWithChannel(function (AMQPChannel $channel) use ($queue) {
            $queueInfo = $channel->queue_declare(
                $queue,
                true, // passive = true: just check, don't create
                true,
                false,
                false
            );
            return $queueInfo[1];
        });
    }

    private function removeMessageByContentPartValidatingIngestionKeys(string $queue, string $contentPart, string $expectedIngestionKeysHeader): void {
        self::doWithChannel(function (AMQPChannel $channel) use ($queue, $contentPart, $expectedIngestionKeysHeader) {
            $messagesCount = self::getMessagesCount($queue);
            for ($i = 0; $i < $messagesCount; $i++) {
                $message = $channel->basic_get($queue);
                $deliveryTag = $message->getDeliveryTag();
                if (str_contains($message->getBody(), $contentPart)) {
                    $channel->basic_ack($deliveryTag); // removes message from queue
                    $this->verifyIngestionKeysHeaderIsCopied($message, $expectedIngestionKeysHeader);
                    return;
                } else {
                    $channel->basic_nack($deliveryTag, false, true); // puts message back to queue (use requeue=true), it's not the one we want to remove
                }
            }
        });
    }

    private function verifyIngestionKeysHeaderIsCopied(AMQPMessage $message, string $expectedIngestionKeysHeader): void {
        $ingestionKeys = Rabbit::readIngestionKeys($message);
        $this->assertEquals($expectedIngestionKeysHeader, $ingestionKeys);
    }

    private static function doWithChannel(callable $function) {
        $connection = new AMQPStreamConnection(
            BaseStreamxTest::RABBIT_MQ_HOST,
            BaseStreamxTest::RABBIT_MQ_PORT,
            BaseStreamxTest::RABBIT_MQ_USER,
            BaseStreamxTest::RABBIT_MQ_PASSWORD
        );
        $channel = $connection->channel();
        $result = $function($channel);
        $connection->close();
        return $result;
    }

    private static function waitUntil(callable $assertion, int $timeoutSeconds = 5, int $intervalMilliseconds = 100): void {
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
}
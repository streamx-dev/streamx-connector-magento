<?php

namespace StreamX\ConnectorCore\test\unit\Client;

use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Publisher\Message;
use StreamX\ConnectorCatalog\Model\Indexer\CategoryProcessor;
use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCore\Client\RabbitMQ\RabbitMqConfiguration;
use StreamX\ConnectorCore\Client\RabbitMQ\RabbitMqIngestionRequestsSender;
use StreamX\ConnectorCore\Client\StreamxClient;
use StreamX\ConnectorCore\Client\StreamxIngestor;

class StreamxClientTest extends TestCase {

    private StreamxClient $clientSpy;

    public function setUp(): void {
        $storeMock = $this->createMock(StoreInterface::class);
        $storeMock->method('getId')->willReturn(5);
        $storeMock->method('getCode')->willReturn('store_5');

        $this->clientSpy = $this
            ->getMockBuilder(StreamxClient::class)
            ->setConstructorArgs([
                $this->createMock(LoggerInterface::class),
                $storeMock,
                $this->createMock(RabbitMqConfiguration::class),
                $this->createMock(RabbitMqIngestionRequestsSender::class),
                $this->createMock(StreamxIngestor::class)
            ])
            ->onlyMethods(['ingest']) // mock only this method to do nothing
            ->getMock();
    }

    /** @test */
    public function verifyPublishKeyAndSxTypeForSimpleProduct() {
        $product = ['id' => '1'];

        $this->publishAndVerifyIngestionMessage(
            $product,
            ProductProcessor::INDEXER_ID,
            'store_5_product:1',
            'product/simple',
            '{"id":"1"}'
        );
    }

    /** @test */
    public function verifyPublishKeyAndSxTypeForConfigurableProduct() {
        $product = [
            'id' => '2',
            'variants' => [
                'id' => '10'
            ]
        ];

        $this->publishAndVerifyIngestionMessage(
            $product,
            ProductProcessor::INDEXER_ID,
            'store_5_product:2',
            'product/master',
            '{"id":"2","variants":{"id":"10"}}'
        );
    }

    /** @test */
    public function verifyUnpublishKeyAndSxTypeForProduct() {
        $productId = 3;

        $this->unpublishAndVerifyIngestionMessage(
            $productId,
            ProductProcessor::INDEXER_ID,
            'store_5_product:3',
            'product'
        );
    }

    /** @test */
    public function verifyPublishKeyAndSxTypeForCategory() {
        $category = ['id' => '4'];

        $this->publishAndVerifyIngestionMessage(
            $category,
            CategoryProcessor::INDEXER_ID,
            'store_5_category:4',
            'category',
            '{"id":"4"}'
        );
    }

    /** @test */
    public function verifyUnpublishKeyAndSxTypeForCategory() {
        $categoryId = 4;

        $this->unpublishAndVerifyIngestionMessage(
            $categoryId,
            CategoryProcessor::INDEXER_ID,
            'store_5_category:4',
            'category'
        );
    }

    private function publishAndVerifyIngestionMessage(array $entityToIngest, string $sourceIndexerId, string $expectedKey, string $expectedSxType, string $expectedPayload) {
        $this->setupIngestionMessageVerification($sourceIndexerId, 'publish', $expectedKey, $expectedSxType, $expectedPayload);
        $this->clientSpy->publish([$entityToIngest], $sourceIndexerId);
    }

    private function unpublishAndVerifyIngestionMessage(int $productId, string $sourceIndexerId, string $expectedKey, string $expectedSxType) {
        $this->setupIngestionMessageVerification($sourceIndexerId, 'unpublish', $expectedKey, $expectedSxType, null);
        $this->clientSpy->unpublish([$productId], $sourceIndexerId);
    }

    private function setupIngestionMessageVerification(string $sourceIndexerId, string $expectedAction, string $expectedKey, string $expectedSxType, ?string $expectedPayload) {
        $this->clientSpy->expects($this->once())
            ->method('ingest')
            ->with(
                $this->callback(function ($ingestionMessagesArg) use ($expectedAction, $expectedKey, $expectedSxType, $expectedPayload) {
                    $this->assertIsArray($ingestionMessagesArg);
                    $this->assertCount(1, $ingestionMessagesArg);
                    $message = $ingestionMessagesArg[0];
                    $this->assertInstanceOf(Message::class, $message);
                    $this->assertEquals($expectedKey, $message->key);
                    $this->assertEquals($expectedSxType, $message->properties->{'sx:type'});
                    $this->assertEquals($expectedAction, $message->action);
                    if ($expectedPayload) {
                        $this->assertEquals($expectedPayload, $message->payload->content->bytes);
                    } else {
                        $this->assertNull($message->payload);
                    }
                    return true;
                }),
                $expectedAction,
                $sourceIndexerId
            );
    }
}

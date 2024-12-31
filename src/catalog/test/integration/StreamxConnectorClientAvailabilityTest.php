<?php

namespace integration;

use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Builders\StreamxClientBuilders;
use StreamX\ConnectorCatalog\test\integration\BaseStreamxTest;
use StreamX\ConnectorCore\Streamx\Client;

class StreamxConnectorClientAvailabilityTest extends BaseStreamxTest {
    private Store $storeMock;
    private StoreManagerInterface $storeManagerMock;
    private LoggerInterface $loggerMock;

    protected function setUp(): void {
        $this->storeMock = $this->createMock(Store::class);
        $this->storeMock->method('getBaseUrl')->willReturn('https://magento-store.com');

        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->storeManagerMock->method('getStore')->willReturn($this->storeMock);

        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->loggerMock->method('error')->will($this->returnCallback(function ($arg) {
            echo $arg; // redirect errors to test console
        }));
    }

    /** @test */
    public function clientShouldBeAvailable() {
        $client = $this->createClient(
            self::STREAMX_REST_INGESTION_URL,
            self::CHANNEL_NAME,
            self::CHANNEL_SCHEMA_NAME
        );
        $this->assertTrue($client->isStreamxAvailable());
    }

    /** @test */
    public function clientShouldNotBeAvailable_WhenNotExistingHost() {
        $client = $this->createClient(
            self::changeHost(self::STREAMX_REST_INGESTION_URL, "c793qwh0uqw3fg94ow"),
            self::CHANNEL_NAME,
            self::CHANNEL_SCHEMA_NAME
        );
        $this->assertFalse($client->isStreamxAvailable());
    }

    /** @test */
    public function clientShouldNotBeAvailable_WhenWrongPort() {
        $client = $this->createClient(
            self::changePort(self::STREAMX_REST_INGESTION_URL, 1234),
            self::CHANNEL_NAME,
            self::CHANNEL_SCHEMA_NAME
        );
        $this->assertFalse($client->isStreamxAvailable());
    }

    /** @test */
    public function clientShouldNotBeAvailable_WhenWrongChannel() {
        $client = $this->createClient(
            self::STREAMX_REST_INGESTION_URL,
            'foo',
            self::CHANNEL_SCHEMA_NAME
        );
        $this->assertFalse($client->isStreamxAvailable());
    }

    /** @test */
    public function clientShouldNotBeAvailable_WhenWrongChannelSchemaName() {
        $client = $this->createClient(
            self::STREAMX_REST_INGESTION_URL,
            self::CHANNEL_NAME,
            'FooIngestionMessage'
        );
        $this->assertFalse($client->isStreamxAvailable());
    }

    private function createClient(string $restIngestionUrl, string $channelName, string $channelSchemaName): Client {
        $publisher = StreamxClientBuilders::create($restIngestionUrl)
            ->build()
            ->newPublisher($channelName, $channelSchemaName);
        return new Client($this->storeManagerMock, $publisher, $this->loggerMock, $channelSchemaName);
    }

    private static function changeHost(string $url, string $newHost): string {
        $parsedUrl = parse_url($url);
        $oldHost = $parsedUrl['host'];
        return str_replace($oldHost, $newHost, $url);
    }

    private static function changePort(string $url, int $newPort): string {
        $parsedUrl = parse_url($url);
        $oldPort = $parsedUrl['port'];
        return str_replace($oldPort, $newPort, $url);
    }
}
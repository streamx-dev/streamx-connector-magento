<?php

namespace integration;

use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Builders\StreamxClientBuilders;
use StreamX\ConnectorCatalog\test\integration\BaseStreamxTest;
use StreamX\ConnectorCore\Streamx\Client;

class StreamxConnectorClientAvailabilityTest extends BaseStreamxTest {

    private const NOT_EXISTING_HOST = 'c793qwh0uqw3fg94ow';
    private const WRONG_INGESTION_PORT = 1234;

    private LoggerInterface $loggerMock;
    private StoreManagerInterface $storeManagerMock;

    protected function setUp(): void {
        $this->setupLoggerMock();
        $this->setupStoreManagerMock();
    }

    private function setupLoggerMock(): void {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->loggerMock->method('error')->will($this->returnCallback(function ($arg) {
            echo $arg; // redirect errors to test console
        }));
    }

    private function setupStoreManagerMock(): void {
        $storeMock = $this->createMock(Store::class);
        $storeMock->method('getBaseUrl')->willReturn('https://dummy-magento-store.com');

        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->storeManagerMock->method('getStore')->willReturn($storeMock);
    }

    /** @test */
    public function clientShouldBeAvailable() {
        // given
        $restIngestionUrl = parent::STREAMX_REST_INGESTION_URL;

        // when
        $client = $this->createClient($restIngestionUrl);

        // then
        $this->assertTrue($client->isStreamxAvailable());
    }

    /** @test */
    public function clientShouldNotBeAvailable_WhenNotExistingHost() {
        // given
        $restIngestionUrl = self::changedRestIngestionUrl('host', self::NOT_EXISTING_HOST);

        // when
        $client = $this->createClient($restIngestionUrl);

        // then
        $this->assertFalse($client->isStreamxAvailable());
    }

    /** @test */
    public function clientShouldNotBeAvailable_WhenWrongPort() {
        // given
        $restIngestionUrl = self::changedRestIngestionUrl('port', self::WRONG_INGESTION_PORT);

        // when
        $client = $this->createClient($restIngestionUrl);

        // then
        $this->assertFalse($client->isStreamxAvailable());
    }

    private function createClient(string $restIngestionUrl): Client {
        $publisher = StreamxClientBuilders::create($restIngestionUrl)
            ->build()
            ->newPublisher(parent::CHANNEL_NAME, parent::CHANNEL_SCHEMA_NAME);
        return new Client($this->loggerMock, $publisher, $this->storeManagerMock);
    }

    private static function changedRestIngestionUrl(string $urlPartName, $newValue): string {
        $parsedUrl = parse_url(parent::STREAMX_REST_INGESTION_URL);
        $oldValue = $parsedUrl[$urlPartName];
        return str_replace($oldValue, $newValue, parent::STREAMX_REST_INGESTION_URL);
    }
}
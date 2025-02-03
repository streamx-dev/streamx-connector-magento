<?php

namespace StreamX\ConnectorCatalog\test\integration;

use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Builders\StreamxClientBuilders;
use StreamX\ConnectorCatalog\test\integration\BaseStreamxTest;
use StreamX\ConnectorCore\Streamx\Client;

class StreamxConnectorClientAvailabilityTest extends BaseStreamxTest {

    private const NOT_EXISTING_HOST = 'c793qwh0uqw3fg94ow';
    private const WRONG_INGESTION_PORT = 1234;

    private LoggerInterface $loggerMock;

    protected function setUp(): void {
        $this->setupLoggerMock();
    }

    private function setupLoggerMock(): void {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->loggerMock->method('error')->will($this->returnCallback(function ($arg) {
            echo $arg; // redirect errors to test console
        }));
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
        return new Client($this->loggerMock, $publisher, 'product_', 'category_');
    }

    private static function changedRestIngestionUrl(string $urlPartName, $newValue): string {
        $parsedUrl = parse_url(parent::STREAMX_REST_INGESTION_URL);
        $oldValue = $parsedUrl[$urlPartName];
        return str_replace($oldValue, $newValue, parent::STREAMX_REST_INGESTION_URL);
    }
}
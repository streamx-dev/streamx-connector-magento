<?php

namespace StreamX\ConnectorCatalog\test\integration;

use Psr\Log\LoggerInterface;
use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCatalog\test\integration\utils\ValidationFileUtils;
use StreamX\ConnectorCore\Client\StreamxClient;
use StreamX\ConnectorCore\Client\StreamxClientConfiguration;

class StreamxConnectorClientAvailabilityTest extends BaseStreamxTest {

    use ValidationFileUtils;

    private const PRODUCT_KEY_PREFIX = 'product_';
    private const CATEGORY_KEY_PREFIX = 'category_';

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

    /** @test */
    public function shouldPublishBigBatchesOfProductsWithoutErrors() {
        // given
        $bigProductJson = $this->readValidationFileContent('original-hoodie-product.json');
        $entity = json_decode($bigProductJson, true);

        $entitiesToPublishInBatch = 100;

        // and: load the same big product to list, but give each instance a unique ID
        $entities = [];
        for ($i = 0; $i < $entitiesToPublishInBatch; $i++) {
            $entities[] = $entity;
            $entities[$i]['id'] = $i;
        }

        // when: publish batch as the Connector would do
        $client = $this->createClient(parent::STREAMX_REST_INGESTION_URL);
        if ($client->isStreamxAvailable()) {
            $client->publish($entities, ProductProcessor::INDEXER_ID);
        }

        // then
        for ($i = 0; $i < $entitiesToPublishInBatch; $i++) {
            $this->assertExactDataIsPublished(self::PRODUCT_KEY_PREFIX . $i,'original-hoodie-product.json', [
                62 => $i // 62 is the product ID in validation file
            ]);
        }

        // and when: unpublish
        $client = $this->createClient(parent::STREAMX_REST_INGESTION_URL);
        if ($client->isStreamxAvailable()) {
            $client->unpublish(array_column($entities, 'id'), ProductProcessor::INDEXER_ID);
        }

        // then
        for ($i = 0; $i < $entitiesToPublishInBatch; $i++) {
            $this->assertDataIsUnpublished(self::PRODUCT_KEY_PREFIX . $i);
        }
    }

    private function createClient(string $restIngestionUrl): StreamxClient {
        $clientConfigurationMock = $this->createMock(StreamxClientConfiguration::class);
        $clientConfigurationMock->method('getIngestionBaseUrl')->willReturn($restIngestionUrl);
        $clientConfigurationMock->method('getChannelName')->willReturn(parent::CHANNEL_NAME);
        $clientConfigurationMock->method('getChannelSchemaName')->willReturn(parent::CHANNEL_SCHEMA_NAME);
        $clientConfigurationMock->method('getAuthToken')->willReturn(null);
        $clientConfigurationMock->method('shouldDisableCertificateValidation')->willReturn(false);
        $clientConfigurationMock->method('getProductKeyPrefix')->willReturn(self::PRODUCT_KEY_PREFIX);
        $clientConfigurationMock->method('getCategoryKeyPrefix')->willReturn(self::CATEGORY_KEY_PREFIX);

        return new StreamxClient($this->loggerMock, $clientConfigurationMock, 1);
    }

    private static function changedRestIngestionUrl(string $urlPartName, $newValue): string {
        $parsedUrl = parse_url(parent::STREAMX_REST_INGESTION_URL);
        $oldValue = $parsedUrl[$urlPartName];
        return str_replace($oldValue, $newValue, parent::STREAMX_REST_INGESTION_URL);
    }
}
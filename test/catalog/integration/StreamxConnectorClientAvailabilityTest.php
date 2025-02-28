<?php

namespace StreamX\ConnectorCatalog\test\integration;

use Magento\Store\Api\Data\StoreInterface;
use Psr\Log\LoggerInterface;
use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCatalog\test\integration\utils\ValidationFileUtils;
use StreamX\ConnectorCore\Client\StreamxClient;
use StreamX\ConnectorCore\Client\StreamxClientConfiguration;

class StreamxConnectorClientAvailabilityTest extends BaseStreamxTest {

    use ValidationFileUtils;

    private const STORE_ID = 1;
    private const STORE_CODE = 'store_1';

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

        self::removeFromStreamX(...array_map(function ($id) {
            return self::expectedStreamxProductKey($id);
        }, array_keys($entities)));

        // when: publish batch as the Connector would do
        $client = $this->createClient(parent::STREAMX_REST_INGESTION_URL);
        if ($client->isStreamxAvailable()) {
            $client->publish($entities, ProductProcessor::INDEXER_ID);
        }

        // then
        for ($i = 0; $i < $entitiesToPublishInBatch; $i++) {
            $this->assertExactDataIsPublished(self::expectedStreamxProductKey($i), 'original-hoodie-product.json', [
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
            $this->assertDataIsUnpublished(self::expectedStreamxProductKey($i));
        }
    }

    private static function expectedStreamxProductKey(int $productId): string {
        return BaseStreamxConnectorPublishTest::productKeyFromEntityId($productId, self::STORE_CODE);
    }

    private function createClient(string $restIngestionUrl): StreamxClient {
        $clientConfigurationMock = $this->createMock(StreamxClientConfiguration::class);
        $clientConfigurationMock->method('getIngestionBaseUrl')->willReturn($restIngestionUrl);
        $clientConfigurationMock->method('getChannelName')->willReturn(parent::CHANNEL_NAME);
        $clientConfigurationMock->method('getChannelSchemaName')->willReturn(parent::CHANNEL_SCHEMA_NAME);
        $clientConfigurationMock->method('getAuthToken')->willReturn(null);
        $clientConfigurationMock->method('shouldDisableCertificateValidation')->willReturn(false);

        $storeMock = $this->createMock(StoreInterface::class);
        $storeMock->method('getId')->willReturn(self::STORE_ID);
        $storeMock->method('getCode')->willReturn(self::STORE_CODE);
        return new StreamxClient($this->loggerMock, $clientConfigurationMock, $storeMock);
    }

    private static function changedRestIngestionUrl(string $urlPartName, $newValue): string {
        $parsedUrl = parse_url(parent::STREAMX_REST_INGESTION_URL);
        $oldValue = $parsedUrl[$urlPartName];
        return str_replace($oldValue, $newValue, parent::STREAMX_REST_INGESTION_URL);
    }
}
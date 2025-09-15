<?php

namespace StreamX\ConnectorCatalog\test\integration;

use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Builders\StreamxClientBuilders;
use StreamX\ConnectorCatalog\test\integration\utils\CodeCoverageReportGenerator;
use StreamX\ConnectorCatalog\test\integration\utils\JsonFormatter;
use StreamX\ConnectorCatalog\test\integration\utils\ValidationFileUtils;
use StreamX\ConnectorCore\Client\RabbitMQ\RabbitMqConfiguration;
use StreamX\ConnectorCore\Client\RabbitMQ\RabbitMqConnectionSettings;
use StreamX\ConnectorCore\Client\RabbitMQ\RabbitMqIngestionRequestsSender;
use StreamX\ConnectorCore\Client\StreamxClient;
use StreamX\ConnectorCore\Client\StreamxClientConfiguration;
use StreamX\ConnectorCore\Client\StreamxIngestor;
use StreamX\ConnectorCore\Client\StreamxPublisherFactory;

/**
 * Prerequisites to run these tests:
 * 1. The 'scripts/install-magento-with-connector.sh' has completed successfully and markshust/docker-magento images are running
 * 2. You have performed the additional manual steps listed at the end of the command's output
 */
abstract class BaseStreamxTest extends TestCase {

    use ValidationFileUtils;

    protected const STREAMX_REST_INGESTION_URL = "http://localhost:8080";
    private const CHANNEL_SCHEMA_NAME = "dev.streamx.blueprints.data.DataIngestionMessage";
    private const CHANNEL_NAME = "data";

    private const STREAMX_SEARCH_SERVICE_URL_TEMPLATE = "http://localhost:9201/default/_search?q=_id:\"%s\"";
    private const WAIT_FOR_INGESTED_DATA_TIMEOUT_SECONDS = 8;
    private const SLEEP_MICROS_BETWEEN_DATA_INGESTION_CHECKS = 200_000;

    // port, user and password are taken from magento/env/rabbitmq.env file
    protected const RABBIT_MQ_HOST = 'localhost';
    protected const RABBIT_MQ_PORT = 5672;
    protected const RABBIT_MQ_API_PORT = 15672;
    protected const RABBIT_MQ_USER = 'magento';
    protected const RABBIT_MQ_PASSWORD = 'magento';

    protected function setUp(): void {
        CodeCoverageReportGenerator::hideCoverageFilesFromPreviousTest();
    }

    protected function tearDown(): void {
        CodeCoverageReportGenerator::generateSingleTestCodeCoverageReport($this);
    }

    /**
     * @param array $regexReplacements what to change in the actual StreamX response Json, to match the validation file
     * @return string the actually published data if assertion passes, or exception if assertion failed
     */
    protected function assertExactDataIsPublished(string $key, string $validationFileName, array $regexReplacements = []): ?string {
        $expectedJson = $this->readValidationFileContent($validationFileName);
        $expectedFormattedJson = JsonFormatter::formatJson($expectedJson);

        $startTime = time();
        $response = null;
        while (time() - $startTime < self::WAIT_FOR_INGESTED_DATA_TIMEOUT_SECONDS) {
            $response = $this->search($key);
            if (!empty($response)) {
                if ($this->verifySameJsonsSilently($expectedFormattedJson, $response, $regexReplacements)) {
                    return $response;
                }
            }
            usleep(self::SLEEP_MICROS_BETWEEN_DATA_INGESTION_CHECKS);
        }

        if ($response !== false) {
            $this->verifySameJsonsOrThrow($expectedFormattedJson, $response, $regexReplacements);
        } else {
            $this->fail("$key: not found");
        }

        return $response;
    }

    protected function assertDataIsUnpublished(string $key): void {
        $startTime = time();
        while (time() - $startTime < self::WAIT_FOR_INGESTED_DATA_TIMEOUT_SECONDS) {
            $response = $this->search($key);
            if (empty($response)) {
                $this->assertTrue(true); // needed to work around the "This test did not perform any assertions" warning
                return;
            }
            usleep(self::SLEEP_MICROS_BETWEEN_DATA_INGESTION_CHECKS);
        }

        $this->fail("$key: exists");
    }

    protected function assertDataIsNotPublished(string $key): void {
        $this->assertDataIsUnpublished($key); // alias
    }

    protected function removeFromStreamX(string ...$keys): void {
        $publisher = StreamxClientBuilders::create(self::STREAMX_REST_INGESTION_URL)
            ->build()
            ->newPublisher(self::CHANNEL_NAME, self::CHANNEL_SCHEMA_NAME);
        foreach ($keys as $key) {
            if ($this->isCurrentlyPublished($key)) {
                $publisher->unpublish($key);
            }
        }
    }

    protected function isCurrentlyPublished(string $key): bool {
        return !empty($this->search($key));
    }

    private function search(string $key): string {
        $url = sprintf(self::STREAMX_SEARCH_SERVICE_URL_TEMPLATE, $key);
        $response = @file_get_contents($url);
        if (empty($response)) {
            return '';
        }

        $data = json_decode($response, true);

        $payloads = array_map(function ($hit) {
            return $hit['_source']['payload'];
        }, $data['hits']['hits']);

        if (empty($payloads)) {
            return '';
        }
        return json_encode($payloads[0]);
    }

    protected function createStreamxClient(): StreamxClient {
        $loggerMock = $this->createLoggerMock();

        $rabbitMqConfigurationMock = $this->createRabbitMqConfigurationMock();
        $rabbitMqSender = new RabbitMqIngestionRequestsSender($loggerMock, $rabbitMqConfigurationMock);

        $clientConfigurationMock = $this->createClientConfigurationMock(self::STREAMX_REST_INGESTION_URL);
        $streamxPublisherFactory = new StreamxPublisherFactory($clientConfigurationMock);
        $streamxIngestor = new StreamxIngestor($loggerMock, $streamxPublisherFactory);

        return new StreamxClient($loggerMock, $rabbitMqConfigurationMock, $rabbitMqSender, $streamxIngestor);
    }

    protected function createLoggerMock(): LoggerInterface {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->method('error')->will($this->returnCallback(function ($arg) {
            echo $arg; // redirect errors to test console
        }));
        return $loggerMock;
    }

    protected function createStoreMock(int $storeId, string $storeCode): StoreInterface {
        $storeMock = $this->createMock(StoreInterface::class);
        $storeMock->method('getId')->willReturn($storeId);
        $storeMock->method('getCode')->willReturn($storeCode);
        return $storeMock;
    }

    protected function createRabbitMqConfigurationMock(): RabbitMqConfiguration {
        $rabbitMqConfigurationMock = $this->createMock(RabbitMqConfiguration::class);
        $rabbitMqConfigurationMock->method('isEnabled')->willReturn(true);
        $rabbitMqConfigurationMock->method('getConnectionSettings')->willReturn(new RabbitMqConnectionSettings(
            self::RABBIT_MQ_HOST,
            self::RABBIT_MQ_PORT,
            self::RABBIT_MQ_USER,
            self::RABBIT_MQ_PASSWORD
        ));
        return $rabbitMqConfigurationMock;
    }

    private function createClientConfigurationMock(string $restIngestionUrl): StreamxClientConfiguration {
        $clientConfigurationMock = $this->createMock(StreamxClientConfiguration::class);
        $clientConfigurationMock->method('getIngestionBaseUrl')->willReturn($restIngestionUrl);
        $clientConfigurationMock->method('getChannelName')->willReturn(self::CHANNEL_NAME);
        $clientConfigurationMock->method('getChannelSchemaName')->willReturn(self::CHANNEL_SCHEMA_NAME);
        $clientConfigurationMock->method('getAuthToken')->willReturn(null);
        $clientConfigurationMock->method('getConnectionTimeout')->willReturn(1);
        $clientConfigurationMock->method('getResponseTimeout')->willReturn(5);
        $clientConfigurationMock->method('shouldDisableCertificateValidation')->willReturn(false);
        return $clientConfigurationMock;
    }
}
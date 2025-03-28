<?php

namespace StreamX\ConnectorCatalog\test\integration;

use Magento\Store\Api\Data\StoreInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Streamx\Clients\Ingestion\Builders\StreamxClientBuilders;
use StreamX\ConnectorCatalog\test\integration\utils\JsonFormatter;
use StreamX\ConnectorCatalog\test\integration\utils\ValidationFileUtils;
use StreamX\ConnectorCore\Client\StreamxClient;
use StreamX\ConnectorCore\Client\StreamxClientConfiguration;

/**
 * Prerequisites to run these tests:
 * 1. markshust/docker-magento images must be running
 * 2. StreamX Connector must be deployed to the Magento instance
 * 3. StreamX Connector must be enabled and configured in Magento (see how-to-setup-local-development-environment.md)
 * 4. StreamX must be running (test/resources/mesh.yaml as minimal mesh setup)
 * 5. scripts/add-rest-ingestion-to-magento-network.sh must be executed
 */
abstract class BaseStreamxTest extends TestCase {

    use ValidationFileUtils;

    protected const STREAMX_REST_INGESTION_URL = "http://localhost:8080";
    private const CHANNEL_SCHEMA_NAME = "dev.streamx.blueprints.data.DataIngestionMessage";
    private const CHANNEL_NAME = "data";

    private const STREAMX_DELIVERY_SERVICE_BASE_URL = "http://localhost:8081";
    private const DATA_PUBLISH_TIMEOUT_SECONDS = 3;
    private const SLEEP_MICROS_BETWEEN_DATA_PUBLISH_CHECKS = 200_000;

    /**
     * @param array $regexReplacements what to change in the actual StreamX response Json, to match the validation file
     * @return string the actually published data if assertion passes, or exception if assertion failed
     */
    protected function assertExactDataIsPublished(string $key, string $validationFileName, array $regexReplacements = []): ?string {
        $url = self::STREAMX_DELIVERY_SERVICE_BASE_URL . '/' . $key;

        $expectedJson = $this->readValidationFileContent($validationFileName);
        $expectedFormattedJson = JsonFormatter::formatJson($expectedJson);

        $startTime = time();
        $response = null;
        while (time() - $startTime < self::DATA_PUBLISH_TIMEOUT_SECONDS) {
            $response = @file_get_contents($url);
            if ($response !== false) {
                if ($this->verifySameJsonsSilently($expectedFormattedJson, $response, $regexReplacements)) {
                    return $response;
                }
            }
            usleep(self::SLEEP_MICROS_BETWEEN_DATA_PUBLISH_CHECKS);
        }

        if ($response !== false) {
            $this->verifySameJsonsOrThrow($expectedFormattedJson, $response, $regexReplacements);
        } else {
            $this->fail("$url: not found");
        }

        return $response;
    }

    protected function downloadContentAtKey(string $key): string {
        $url = self::STREAMX_DELIVERY_SERVICE_BASE_URL . '/' . $key;

        $startTime = time();
        while (time() - $startTime < self::DATA_PUBLISH_TIMEOUT_SECONDS) {
            $response = @file_get_contents($url);
            if ($response !== false) {
                return $response;
            }
            usleep(self::SLEEP_MICROS_BETWEEN_DATA_PUBLISH_CHECKS);
        }

        $this->fail("$url: not found");
    }

    protected function assertDataIsUnpublished(string $key): void {
        $url = self::STREAMX_DELIVERY_SERVICE_BASE_URL . '/' . $key;

        $startTime = time();
        while (time() - $startTime < self::DATA_PUBLISH_TIMEOUT_SECONDS) {
            $response = @file_get_contents($url);
            if (empty($response)) {
                $this->assertTrue(true); // needed to work around the "This test did not perform any assertions" warning
                return;
            }
            usleep(self::SLEEP_MICROS_BETWEEN_DATA_PUBLISH_CHECKS);
        }

        $this->fail("$url: exists");
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

    private function isCurrentlyPublished(string $key): bool {
        $url = self::STREAMX_DELIVERY_SERVICE_BASE_URL . '/' . $key;
        $headers = @get_headers($url);
        if ($headers === false) {
            return false;
        }
        return str_contains($headers[0], "200 OK");
    }

    protected function createStreamxClient(int $storeId, string $storeCode): StreamxClient {
        return $this->createCustomStreamxClient($storeId, $storeCode, self::STREAMX_REST_INGESTION_URL);
    }

    protected function createCustomStreamxClient(int $storeId, string $storeCode, string $restIngestionUrl): StreamxClient {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->method('error')->will($this->returnCallback(function ($arg) {
            echo $arg; // redirect errors to test console
        }));

        $clientConfigurationMock = $this->createMock(StreamxClientConfiguration::class);
        $clientConfigurationMock->method('getIngestionBaseUrl')->willReturn($restIngestionUrl);
        $clientConfigurationMock->method('getChannelName')->willReturn(self::CHANNEL_NAME);
        $clientConfigurationMock->method('getChannelSchemaName')->willReturn(self::CHANNEL_SCHEMA_NAME);
        $clientConfigurationMock->method('getAuthToken')->willReturn(null);
        $clientConfigurationMock->method('shouldDisableCertificateValidation')->willReturn(false);

        $storeMock = $this->createMock(StoreInterface::class);
        $storeMock->method('getId')->willReturn($storeId);
        $storeMock->method('getCode')->willReturn($storeCode);
        return new StreamxClient($loggerMock, $clientConfigurationMock, $storeMock);
    }
}
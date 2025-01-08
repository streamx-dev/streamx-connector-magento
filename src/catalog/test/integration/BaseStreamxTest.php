<?php

namespace StreamX\ConnectorCatalog\test\integration;

use PHPUnit\Framework\TestCase;
use Streamx\Clients\Ingestion\Builders\StreamxClientBuilders;

/**
 * Prerequisites to run these tests:
 * 1. markshust/docker-magento images must be running
 * 2. StreamX Connector must be deployed to the Magento instance
 * 3. StreamX Connector must be enabled and configured in Magento
 * 4. StreamX must be running (with the add-rest-ingestion-to-magento-network.sh script executed)
 */
abstract class BaseStreamxTest extends TestCase {

    protected const STREAMX_REST_INGESTION_URL = "http://localhost:8080";
    protected const CHANNEL_SCHEMA_NAME = "dev.streamx.blueprints.data.DataIngestionMessage";
    protected const CHANNEL_NAME = "data";

    private const STREAMX_DELIVERY_SERVICE_BASE_URL = "http://localhost:8081";
    private const DATA_PUBLISH_TIMEOUT_SECONDS = 3;

    /**
     * @deprecated move to use assertExactDataIsPublished instead, as it gives more exact verification
     */
    protected function assertDataIsPublished(string $key, string $contentSubstring): void {
        $url = self::STREAMX_DELIVERY_SERVICE_BASE_URL . '/' . $key;

        $startTime = time();
        $response = null;
        while (time() - $startTime < self::DATA_PUBLISH_TIMEOUT_SECONDS) {
            $response = @file_get_contents($url);
            if ($response !== false) {
                echo "Published content: $response\n";
                if (str_contains($response, $contentSubstring)) {
                    $this->assertTrue(true); // needed to work around the "This test did not perform any assertions" warning
                    return;
                }
            }
            usleep(100000); // sleep for 100 milliseconds
        }

        if ($response !== false) {
            $this->assertStringContainsString($contentSubstring, $response);
        } else {
            $this->fail("$url: not found");
        }
    }

    protected function assertExactDataIsPublished(string $key, string $expectedJson): void {
        $url = self::STREAMX_DELIVERY_SERVICE_BASE_URL . '/' . $key;
        $expectedFormattedJson = self::formatJson($expectedJson);
        $actualFormattedJson = '';

        $startTime = time();
        $response = null;
        while (time() - $startTime < self::DATA_PUBLISH_TIMEOUT_SECONDS) {
            $response = @file_get_contents($url);
            if ($response !== false) {
                echo "Published content: $response\n";
                $actualFormattedJson = self::formatJson($response);
                if ($expectedFormattedJson === $actualFormattedJson) {
                    $this->assertTrue(true); // needed to work around the "This test did not perform any assertions" warning
                    return;
                }
            }
            usleep(100000); // sleep for 100 milliseconds
        }

        if ($response !== false) {
            $this->assertEquals($expectedFormattedJson, $actualFormattedJson);
        } else {
            $this->fail("$url: not found");
        }
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
            usleep(100000); // sleep for 100 milliseconds
        }

        $this->fail("$url: exists");
    }

    protected function removeFromStreamX(string $key): void {
        StreamxClientBuilders::create(self::STREAMX_REST_INGESTION_URL)
            ->build()
            ->newPublisher(self::CHANNEL_NAME, self::CHANNEL_SCHEMA_NAME)
            ->unpublish($key);
    }

    private static function formatJson(string $json): string {
        return json_encode(json_decode($json), JSON_PRETTY_PRINT);
    }
}
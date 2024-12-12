<?php

namespace StreamX\ConnectorCatalog\test\integration;

use PHPUnit\Framework\TestCase;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoIndexerOperationsExecutor;

/**
 * Prerequisites to run these tests:
 * 1. markshust/docker-magento images must be running
 * 2. StreamX Connector must be deployed to the Magento instance
 * 3. StreamX must be running (with the add-rest-ingestion-to-magento-network.sh script executed)
 */
abstract class BaseStreamxPublishTest extends TestCase {

    private const STREAMX_DELIVERY_SERVICE_BASE_URL = "http://localhost:8081";
    private const DATA_PUBLISH_TIMEOUT_SECONDS = 10;

    protected MagentoIndexerOperationsExecutor $indexerOperations;
    private string $originalIndexerMode;
    private bool $indexModeNeedsRestoring;

    protected abstract function indexerName(): string;
    protected abstract function desiredIndexerMode(): string;

    public function setUp(): void {
        $this->indexerOperations = new MagentoIndexerOperationsExecutor($this->indexerName());
        $this->originalIndexerMode = $this->indexerOperations->getIndexerMode();

        if ($this->desiredIndexerMode() !== $this->originalIndexerMode) {
            $this->indexerOperations->setIndexerMode($this->desiredIndexerMode());
            $this->indexModeNeedsRestoring = true;
        } else {
            $this->indexModeNeedsRestoring = false;
        }
    }

    public function tearDown(): void {
        if ($this->indexModeNeedsRestoring) {
            $this->indexerOperations->setIndexerMode($this->originalIndexerMode);
        }
    }

    protected function assertDataIsPublished(string $key, string $contentSubstring) {
        $url = self::STREAMX_DELIVERY_SERVICE_BASE_URL . '/' . $key;

        $startTime = time();
        while (time() - $startTime < self::DATA_PUBLISH_TIMEOUT_SECONDS) {
            $response = @file_get_contents($url);
            if ($response !== false) {
                echo "Published content: $response\n";
                $this->assertStringContainsString($contentSubstring, $response);
                return;
            }
            usleep(100000); // sleep for 100 milliseconds
        }

        $this->fail("$url: not found");
    }

    protected function assertDataIsUnpublished(string $key) {
        $url = self::STREAMX_DELIVERY_SERVICE_BASE_URL . '/' . $key;

        $startTime = time();
        while (time() - $startTime < self::DATA_PUBLISH_TIMEOUT_SECONDS) {
            $response = @file_get_contents($url);
            if (empty($response)) {
                $this->assertTrue(true);
                return;
            }
            usleep(100000); // sleep for 100 milliseconds
        }

        $this->fail("$url: exists");
    }
}
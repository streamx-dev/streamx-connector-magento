<?php

namespace StreamX\ConnectorCatalog\test\integration;

use PHPUnit\Framework\TestCase;
use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoIndexerOperationsExecutor;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoMySqlQueryExecutor;
use function date;

/**
 * Prerequisites to run these tests:
 * 1. markshust/docker-magento images must be running
 * 2. StreamX must be running (with the add-rest-ingestion-to-magento-network.sh script executed)
 */
abstract class BaseEditedEntityStreamxPublishTest extends TestCase {

    private const STREAMX_DELIVERY_SERVICE_BASE_URL = "http://localhost:8081";
    private const DATA_PUBLISH_TIMEOUT_SECONDS = 10;

    private bool $wasProductIndexerOriginallyInUpdateOnSaveMode;
    protected MagentoIndexerOperationsExecutor $indexerOperations;

    protected abstract function getIndexerName(): string;

    public function setUp(): void {
        $this->indexerOperations = new MagentoIndexerOperationsExecutor($this->getIndexerName());

        // read original mode of the indexer
        $this->wasProductIndexerOriginallyInUpdateOnSaveMode = str_contains(
            $this->indexerOperations->executeCommand('show-mode'),
            MagentoIndexerOperationsExecutor::UPDATE_ON_SAVE_DISPLAY_NAME
        );

        // change to scheduled if needed
        if ($this->wasProductIndexerOriginallyInUpdateOnSaveMode) {
            $this->indexerOperations->setProductIndexerModeToUpdateBySchedule();
        }
    }

    public function tearDown(): void {
        // restore mode of indexer if needed
        if ($this->wasProductIndexerOriginallyInUpdateOnSaveMode) {
            $this->indexerOperations->setProductIndexerModeToUpdateOnSave();
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
}
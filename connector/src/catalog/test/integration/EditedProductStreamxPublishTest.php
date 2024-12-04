<?php

namespace StreamX\ConnectorCatalog\test\integration;

use PHPUnit\Framework\TestCase;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoIndexerOperationsExecutor;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoMySqlQueryExecutor;
use function date;

/**
 * Prerequisites to running this test
 * 1. markshust/docker-magento images must be running
 * 2. StreamX must be running (with the add-rest-ingestion-to-magento-network.sh script executed)
 */
class EditedProductStreamxPublishTest extends TestCase {

    private const STREAMX_DELIVERY_SERVICE_BASE_URL = "http://localhost:8081";
    private const TIMEOUT_SECONDS = 10;

    private MagentoIndexerOperationsExecutor $indexerOperations;

    protected function setUp(): void {
        $this->indexerOperations = new MagentoIndexerOperationsExecutor();
    }

    /** @test */
    public function shouldPublishProductEditedDirectlyInDatabaseToStreamx() {
        // given
        $productSku = '24-MB01';
        $productNewName = 'Name modified for testing, at ' . date("Y-m-d H:i:s");

        // 1. Get current mode of indexer
        $wasProductIndexerOriginallyInUpdateOnSaveMode = str_contains(
            $this->indexerOperations->executeCommand('show-mode'),
            MagentoIndexerOperationsExecutor::UPDATE_ON_SAVE_DISPLAY_NAME
        );

        // 2. Change to scheduled if needed
        if ($wasProductIndexerOriginallyInUpdateOnSaveMode) {
            $this->indexerOperations->setProductIndexerModeToUpdateBySchedule();
        }

        // 3. Read current name of the test product
        $productId = MagentoMySqlQueryExecutor::selectFirstField(<<<EOD
            SELECT entity_id 
              FROM catalog_product_entity
             WHERE sku = '$productSku'
        EOD);

        $entityTypeId = MagentoMySqlQueryExecutor::selectFirstField(<<<EOD
            SELECT entity_type_id
              FROM eav_entity_type
             WHERE entity_table = 'catalog_product_entity'
        EOD);

        $productNameAttributeId = MagentoMySqlQueryExecutor::selectFirstField(<<<EOD
            SELECT attribute_id
              FROM eav_attribute
             WHERE attribute_code = 'name'
               AND entity_type_id = $entityTypeId
        EOD);

        $productOldName = MagentoMySqlQueryExecutor::selectFirstField(<<<EOD
            SELECT value
              FROM catalog_product_entity_varchar
             WHERE attribute_id = $productNameAttributeId
               AND entity_id = $productId
        EOD);
        $this->assertNotEquals($productNewName, $productOldName);

        // 4. Perform direct DB modification of a product
        MagentoMySqlQueryExecutor::execute(<<<EOD
            UPDATE catalog_product_entity_varchar
               SET value = '$productNewName'
             WHERE attribute_id = $productNameAttributeId
               AND entity_id = $productId
        EOD);

        // 5. Trigger reindexing
        $this->indexerOperations->executeCommand('reindex');

        // 6. Assert product is published to StreamX
        try {
            $expectedKey = "product_$productSku";
            $this->assertPageIsPublished($expectedKey, $productNewName);
        } finally {
            // 6. Restore product name in DB
            MagentoMySqlQueryExecutor::execute(<<<EOD
                UPDATE catalog_product_entity_varchar
                   SET value = '$productOldName'
                 WHERE attribute_id = $productNameAttributeId
                   AND entity_id = $productId
            EOD);

            // 7. Restore mode of indexer
            if ($wasProductIndexerOriginallyInUpdateOnSaveMode) {
                $this->indexerOperations->setProductIndexerModeToUpdateOnSave();
            }
        }
    }

    private function assertPageIsPublished(string $key, string $contentSubstring) {
        $url = self::STREAMX_DELIVERY_SERVICE_BASE_URL . '/' . $key;

        $startTime = time();
        while (time() - $startTime < self::TIMEOUT_SECONDS) {
            $response = @file_get_contents($url);
            if ($response !== false) {
                $this->assertStringContainsString($contentSubstring, $response);
                return;
            }
            usleep(100000); // sleep for 100 milliseconds
        }

        $this->fail("$url: page not found");
    }
}
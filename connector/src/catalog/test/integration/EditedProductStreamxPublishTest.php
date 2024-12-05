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

    private static MagentoIndexerOperationsExecutor $indexerOperations;
    private static bool $wasProductIndexerOriginallyInUpdateOnSaveMode;

    public static function setUpBeforeClass(): void {
        self::$indexerOperations = new MagentoIndexerOperationsExecutor();

        // read original mode of the indexer
        self::$wasProductIndexerOriginallyInUpdateOnSaveMode = str_contains(
            self::$indexerOperations->executeCommand('show-mode'),
            MagentoIndexerOperationsExecutor::UPDATE_ON_SAVE_DISPLAY_NAME
        );

        // change to scheduled if needed
        if (self::$wasProductIndexerOriginallyInUpdateOnSaveMode) {
            self::$indexerOperations->setProductIndexerModeToUpdateBySchedule();
        }
    }

    public static function tearDownAfterClass(): void {
        // restore mode of indexer if needed
        if (self::$wasProductIndexerOriginallyInUpdateOnSaveMode) {
            self::$indexerOperations->setProductIndexerModeToUpdateOnSave();
        }
    }

    /** @test */
    public function shouldPublishProductEditedDirectlyInDatabaseToStreamx() {
        // given
        $productId = '1';
        $productNewName = 'Name modified for testing, at ' . date("Y-m-d H:i:s");

        // 1. Read current name of the test product
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

        // 2. Perform direct DB modification of a product
        MagentoMySqlQueryExecutor::execute(<<<EOD
            UPDATE catalog_product_entity_varchar
               SET value = '$productNewName'
             WHERE attribute_id = $productNameAttributeId
               AND entity_id = $productId
        EOD);

        // 3. Trigger reindexing
        self::$indexerOperations->executeCommand('reindex');

        // 4. Assert product is published to StreamX
        try {
            $expectedKey = "product_$productId";
            $this->assertPageIsPublished($expectedKey, $productNewName);
        } finally {
            // 5. Restore product name in DB
            MagentoMySqlQueryExecutor::execute(<<<EOD
                UPDATE catalog_product_entity_varchar
                   SET value = '$productOldName'
                 WHERE attribute_id = $productNameAttributeId
                   AND entity_id = $productId
            EOD);
        }

        // 5. Additionally, verify if as a result of full reindex made at app start, categories are also published
        // TODO: set up dedicated test for category indexer, and move this assertion there
        $this->assertPageIsPublished('category_2', 'Default Category');
    }

    private function assertPageIsPublished(string $key, string $contentSubstring) {
        $url = self::STREAMX_DELIVERY_SERVICE_BASE_URL . '/' . $key;

        $startTime = time();
        while (time() - $startTime < self::TIMEOUT_SECONDS) {
            $response = @file_get_contents($url);
            if ($response !== false) {
                echo "Published page content: $response\n";
                $this->assertStringContainsString($contentSubstring, $response);
                return;
            }
            usleep(100000); // sleep for 100 milliseconds
        }

        $this->fail("$url: page not found");
    }
}
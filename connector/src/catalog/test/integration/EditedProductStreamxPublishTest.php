<?php

namespace StreamX\ConnectorCatalog\test\integration;

use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoMySqlQueryExecutor;
use function date;

/**
 * See base class for prerequisites to run this test
 */
class EditedProductStreamxPublishTest extends BaseEditedEntityStreamxPublishTest {

    protected function getIndexerName(): string {
        return ProductProcessor::INDEXER_ID;
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
        $this->indexerOperations->executeCommand('reindex');

        // 4. Assert product is published to StreamX
        try {
            $expectedKey = "product_$productId";
            $this->assertDataIsPublished($expectedKey, $productNewName);
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
        $this->assertDataIsPublished('category_2', 'Default Category');
    }
}
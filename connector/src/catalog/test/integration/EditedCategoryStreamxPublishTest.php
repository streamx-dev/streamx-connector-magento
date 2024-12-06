<?php

namespace StreamX\ConnectorCatalog\test\integration;

use StreamX\ConnectorCatalog\Model\Indexer\CategoryProcessor;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoMySqlQueryExecutor;
use function date;

/**
 * See base class for prerequisites to run this test
 */
class EditedCategoryStreamxPublishTest extends BaseEditedEntityStreamxPublishTest {

    protected function getIndexerName(): string {
        return CategoryProcessor::INDEXER_ID;
    }

    /** @test */
    public function shouldPublishCategoryEditedDirectlyInDatabaseToStreamx() {
        // given
        $categoryId = '6';
        $categoryNewName = 'Name modified for testing, at ' . date("Y-m-d H:i:s");

        // 1. Read current name of the test category
        $entityTypeId = MagentoMySqlQueryExecutor::selectFirstField(<<<EOD
            SELECT entity_type_id
              FROM eav_entity_type
             WHERE entity_table = 'catalog_category_entity'
        EOD);

        $categoryNameAttributeId = MagentoMySqlQueryExecutor::selectFirstField(<<<EOD
            SELECT attribute_id
              FROM eav_attribute
             WHERE attribute_code = 'name'
               AND entity_type_id = $entityTypeId
        EOD);

        $categoryOldName = MagentoMySqlQueryExecutor::selectFirstField(<<<EOD
            SELECT value
              FROM catalog_category_entity_varchar
             WHERE attribute_id = $categoryNameAttributeId
               AND entity_id = $categoryId
        EOD);
        $this->assertNotEquals($categoryNewName, $categoryOldName);

        // 2. Perform direct DB modification of a category
        MagentoMySqlQueryExecutor::execute(<<<EOD
            UPDATE catalog_category_entity_varchar
               SET value = '$categoryNewName'
             WHERE attribute_id = $categoryNameAttributeId
               AND entity_id = $categoryId
        EOD);

        // 3. Trigger reindexing
        $this->indexerOperations->executeCommand('reindex');

        // 4. Assert category is published to StreamX
        try {
            $expectedKey = "category_$categoryId";
            $this->assertDataIsPublished($expectedKey, $categoryNewName);
        } finally {
            // 5. Restore category name in DB
            MagentoMySqlQueryExecutor::execute(<<<EOD
                UPDATE catalog_category_entity_varchar
                   SET value = '$categoryOldName'
                 WHERE attribute_id = $categoryNameAttributeId
                   AND entity_id = $categoryId
            EOD);
        }
    }
}
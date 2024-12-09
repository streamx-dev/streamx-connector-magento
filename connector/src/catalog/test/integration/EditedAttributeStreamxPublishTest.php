<?php

namespace StreamX\ConnectorCatalog\test\integration;

use StreamX\ConnectorCatalog\Model\Indexer\AttributeProcessor;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoMySqlQueryExecutor;
use function date;

/**
 * See base class for prerequisites to run this test
 */
class EditedAttributeStreamxPublishTest extends BaseEditedEntityStreamxPublishTest {

    protected function getIndexerName(): string {
        return AttributeProcessor::INDEXER_ID;
    }

    /** @test */
    public function shouldPublishAttributeEditedDirectlyInDatabaseToStreamx() {
        // given
        $attributeCode = 'description';
        $newDisplayName = 'Description attribute name modified for testing, at ' . date("Y-m-d H:i:s");

        // 1. Read current display name of the Description attribute
        $productEntityTypeId = MagentoMySqlQueryExecutor::selectFirstField(<<<EOD
            SELECT entity_type_id
              FROM eav_entity_type
             WHERE entity_table = 'catalog_product_entity'
        EOD);

        $attributeId = MagentoMySqlQueryExecutor::selectFirstField(<<<EOD
            SELECT attribute_id
              FROM eav_attribute
             WHERE attribute_code = '$attributeCode'
               AND entity_type_id = $productEntityTypeId
        EOD);

        $oldDisplayName = MagentoMySqlQueryExecutor::selectFirstField(<<<EOD
            SELECT frontend_label
              FROM eav_attribute
             WHERE attribute_id = $attributeId
        EOD);
        $this->assertNotEquals($newDisplayName, $oldDisplayName);

        // 2. Perform direct DB modification of a attribute
        MagentoMySqlQueryExecutor::execute(<<<EOD
            UPDATE eav_attribute
               SET frontend_label = '$newDisplayName'
             WHERE attribute_id = $attributeId
        EOD);

        // 3. Trigger reindexing
        $this->indexerOperations->executeCommand('reindex');

        // 4. Assert attribute is published to StreamX
        try {
            $expectedKey = "attribute_$attributeId";
            $this->assertDataIsPublished($expectedKey, $newDisplayName);
        } finally {
            // 5. Restore attribute name in DB
            MagentoMySqlQueryExecutor::execute(<<<EOD
                UPDATE eav_attribute
                   SET frontend_label = '$oldDisplayName'
                 WHERE attribute_id = $attributeId
            EOD);
        }
    }
}
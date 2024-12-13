<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\Indexer\AttributeProcessor;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoMySqlQueryExecutor;
use function date;

/**
 * @inheritdoc
 */
class DirectDbAttributeUpdateStreamxPublishTest extends BaseDirectDbEntityUpdateStreamxPublishTest {

    protected function indexerName(): string {
        return AttributeProcessor::INDEXER_ID;
    }

    /** @test */
    public function shouldPublishAttributeEditedDirectlyInDatabaseToStreamx() {
        // given
        $attributeCode = 'description';
        $attributeId = MagentoMySqlQueryExecutor::getProductAttributeId($attributeCode);

        $newDisplayName = 'Description attribute name modified for testing, at ' . date("Y-m-d H:i:s");
        $oldDisplayName = MagentoMySqlQueryExecutor::getAttributeDisplayName($attributeId);

        // when
        self::renameAttributeInDb($attributeId, $newDisplayName);
        $this->indexerOperations->reindex();

        // then
        try {
            $expectedKey = "attribute_$attributeId";
            $this->assertDataIsPublished($expectedKey, $newDisplayName);
        } finally {
            self::renameAttributeInDb($attributeId, $oldDisplayName);
        }
    }

    private static function renameAttributeInDb($attributeId, string $newDisplayName): void {
        MagentoMySqlQueryExecutor::execute("
            UPDATE eav_attribute
               SET frontend_label = '$newDisplayName'
             WHERE attribute_id = $attributeId
        ");
    }
}
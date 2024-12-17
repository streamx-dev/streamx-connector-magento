<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\Indexer\AttributeProcessor;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoMySqlQueryExecutor;
use function date;

/**
 * @inheritdoc
 */
class AttributeUpdateTest extends BaseDirectDbEntityUpdateTest {

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

        // and
        $expectedKey = "attribute_$attributeId";
        self::removeFromStreamX($expectedKey);

        // when
        self::renameAttributeInDb($attributeId, $newDisplayName);
        $this->reindexMview();

        // then
        try {
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
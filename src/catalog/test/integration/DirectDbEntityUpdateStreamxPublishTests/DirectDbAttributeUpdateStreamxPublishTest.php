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
        $attributeId = MagentoMySqlQueryExecutor::getAttributeId($attributeCode);

        $newDisplayName = 'Description attribute name modified for testing, at ' . date("Y-m-d H:i:s");
        $oldDisplayName = MagentoMySqlQueryExecutor::getAttributeDisplayName($attributeId);

        // when
        self::renameAttribute($attributeId, $newDisplayName);
        $this->indexerOperations->reindex();

        // then
        try {
            $expectedKey = "attribute_$attributeId";
            $this->assertDataIsPublished($expectedKey, $newDisplayName);
        } finally {
            self::renameAttribute($attributeId, $oldDisplayName);
        }
    }

    private static function renameAttribute($attributeId, string $newDisplayName): void {
        MagentoMySqlQueryExecutor::execute(<<<EOD
            UPDATE eav_attribute
               SET frontend_label = '$newDisplayName'
             WHERE attribute_id = $attributeId
        EOD);
    }
}
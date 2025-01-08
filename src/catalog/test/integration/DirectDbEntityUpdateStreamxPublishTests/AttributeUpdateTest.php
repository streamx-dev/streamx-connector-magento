<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\Indexer\AttributeProcessor;
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
        $attributeId = $this->db->getProductAttributeId($attributeCode);

        $newDisplayName = 'Description attribute name modified for testing, at ' . date("Y-m-d H:i:s");
        $oldDisplayName = $this->db->getAttributeDisplayName($attributeId);

        // and
        $expectedKey = "attribute_$attributeId";
        self::removeFromStreamX($expectedKey);

        // when
        $this->renameAttributeInDb($attributeId, $newDisplayName);

        try {
            // and
            $this->reindexMview();

            // then
            $this->assertDataIsPublished($expectedKey, $newDisplayName);
        } finally {
            $this->renameAttributeInDb($attributeId, $oldDisplayName);
        }
    }

    private function renameAttributeInDb($attributeId, string $newDisplayName): void {
        $this->db->execute("
            UPDATE eav_attribute
               SET frontend_label = '$newDisplayName'
             WHERE attribute_id = $attributeId
        ");
    }
}
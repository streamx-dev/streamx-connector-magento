<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\Indexer\AttributeProcessor;

/**
 * @inheritdoc
 */
class AttributeUpdateTest extends BaseDirectDbEntityUpdateTest {

    protected function indexerName(): string {
        return AttributeProcessor::INDEXER_ID;
    }

    /** @test */
    public function shouldPublishSimpleAttributeEditedDirectlyInDatabaseToStreamx() {
        $this->shouldPublishAttributeEditedDirectlyInDatabaseToStreamx('description');
    }

    /** @test */
    public function shouldPublishAttributeWithOptionsEditedDirectlyInDatabaseToStreamx() {
        $this->shouldPublishAttributeEditedDirectlyInDatabaseToStreamx('size');
    }

    private function shouldPublishAttributeEditedDirectlyInDatabaseToStreamx(string $attributeCode): void {
        // given
        $attributeId = $this->db->getProductAttributeId($attributeCode);

        $newDisplayName = "$attributeCode attribute name modified for testing";
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
            $this->assertExactDataIsPublished($expectedKey, "edited-$attributeCode-attribute.json");
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
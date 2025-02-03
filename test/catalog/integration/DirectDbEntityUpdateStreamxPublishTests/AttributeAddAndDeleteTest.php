<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests\BaseDirectDbEntityUpdateTest;
use StreamX\ConnectorCatalog\Model\Indexer\AttributeProcessor;

/**
 * @inheritdoc
 */
class AttributeAddAndDeleteTest extends BaseDirectDbEntityUpdateTest {

    protected function indexerName(): string {
        return AttributeProcessor::INDEXER_ID;
    }

    /** @test */
    public function shouldPublishAttributeAddedDirectlyInDatabaseToStreamx_AndUnpublishDeletedAttribute() {
        // given
        $attributeCode = 'the_new_attribute';

        // when
        $attributeId = $this->insertNewAttribute($attributeCode);
        $expectedKey = "attr:$attributeId";

        try {
            // and
            $this->reindexMview();

            // then
            $this->assertDataIsPublished($expectedKey, $attributeCode);
        } finally {
            // and when
            $this->deleteAttribute($attributeId);
            $this->reindexMview();

            // then
            $this->assertDataIsUnpublished($expectedKey);
        }
    }

    /**
     * Inserts new attribute to database
     * @return int ID of the inserted attribute
     */
    private function insertNewAttribute(string $attributeCode): int {
        $attributeName = "Display name of $attributeCode";
        $entityTypeId = $this->db->getProductEntityTypeId();

        $this->db->execute("
            INSERT INTO eav_attribute (entity_type_id, attribute_code, frontend_label, backend_type, frontend_input, is_user_defined) VALUES
                ($entityTypeId, '$attributeCode', '$attributeName', 'text', 'textarea', TRUE)
        ");

        $attributeId = $this->db->selectMaxId('eav_attribute', 'attribute_id');

        $this->db->execute("
            INSERT INTO catalog_eav_attribute (attribute_id, is_visible, is_visible_on_front, used_in_product_listing, is_visible_in_advanced_search) VALUES
                ($attributeId, TRUE, TRUE, TRUE, TRUE)
        ");

        return $attributeId;
    }

    private function deleteAttribute(int $attributeId): void {
        $this->db->executeAll([
            "DELETE FROM catalog_eav_attribute WHERE attribute_id = $attributeId",
            "DELETE FROM eav_attribute WHERE attribute_id = $attributeId"
        ]);
    }
}
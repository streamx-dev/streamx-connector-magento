<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\Indexer\AttributeProcessor;

/**
 * @inheritdoc
 */
class AttributeAddAndDeleteTest extends BaseDirectDbEntityUpdateTest {

    protected function indexerName(): string {
        return AttributeProcessor::INDEXER_ID;
    }

    /** @test */
    public function shouldPublishProductThatUsesAttributeAddedDirectlyInDatabaseToStreamx() {
        // given
        $attributeCode = 'the_new_attribute';
        $productId = $this->db->getProductId('Sprite Foam Roller');

        // and
        $expectedKey = "pim:$productId";
        $this->removeFromStreamX($expectedKey);

        // when
        $this->allowIndexingAllAttributes();
        $attributeId = $this->insertNewAttribute($attributeCode, $productId);

        try {
            // and
            $this->reindexMview();

            // then
            $this->assertExactDataIsPublished($expectedKey, 'edited-roller-product-with-custom-attribute.json');
        } finally {
            try {
                // and when
                $this->deleteAttribute($attributeId);
                $this->reindexMview();

                // then
                // note: we don't implement code to retrieve (and republish) product that used a deleted attribute, so the product is not republished, its last published version still has the custom attribute:
                $this->assertExactDataIsPublished($expectedKey, 'edited-roller-product-with-custom-attribute.json');
            } finally {
                $this->restoreDefaultIndexingAttributes();
            }
        }
    }

    /**
     * Inserts new attribute to database along with adding it to the provided product
     * @return int ID of the inserted attribute
     */
    private function insertNewAttribute(string $attributeCode, int $productId): int {
        $attributeName = "Display name of $attributeCode";
        $entityTypeId = $this->db->getProductEntityTypeId();
        $defaultStoreId = 0;

        $attributeId = $this->db->insert("
            INSERT INTO eav_attribute (entity_type_id, attribute_code, frontend_label, backend_type, frontend_input, is_user_defined) VALUES
                ($entityTypeId, '$attributeCode', '$attributeName', 'varchar', 'text', TRUE)
        ");

        $this->db->execute("
            INSERT INTO catalog_eav_attribute (attribute_id, is_visible, is_visible_on_front, used_in_product_listing, is_visible_in_advanced_search) VALUES
                ($attributeId, TRUE, TRUE, TRUE, TRUE)
        ");

        // add attribute to product
        $this->db->execute("
            INSERT INTO catalog_product_entity_varchar (entity_id, attribute_id, store_id, value) VALUES
                ($productId, $attributeId, $defaultStoreId, '$attributeCode value for product $productId')
        ");

        return $attributeId;
    }

    private function deleteAttribute(int $attributeId): void {
        $this->db->executeAll([
            "DELETE FROM catalog_product_entity_varchar WHERE attribute_id = $attributeId",
            "DELETE FROM catalog_eav_attribute WHERE attribute_id = $attributeId",
            "DELETE FROM eav_attribute WHERE attribute_id = $attributeId"
        ]);
    }
}
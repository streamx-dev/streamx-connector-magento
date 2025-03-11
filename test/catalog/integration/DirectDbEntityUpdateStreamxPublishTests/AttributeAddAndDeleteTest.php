<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\utils\EntityIds;

/**
 * @inheritdoc
 * @UsesAttributeIndexer
 */
class AttributeAddAndDeleteTest extends BaseDirectDbEntityUpdateTest {

    /** @test */
    public function shouldPublishProductThatUsesAttributeAddedDirectlyInDatabaseToStreamx() {
        // given
        $attributeCode = 'the_new_attribute';
        $attributeLabel = 'The New Attribute';
        $productId = self::$db->getProductId('Sprite Foam Roller');

        // and
        $expectedKey = self::productKey($productId);
        $this->removeFromStreamX($expectedKey);

        // when
        $this->setIndexedProductAttributes('the_new_attribute');
        $attributeId = $this->insertNewAttribute($attributeCode, $attributeLabel, $productId);

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
                $this->restoreDefaultIndexedProductAttributes();
            }
        }
    }

    /**
     * Inserts new attribute to database along with adding it to the provided product
     * @return int ID of the inserted attribute
     */
    private function insertNewAttribute(string $attributeCode, string $attributeLabel, EntityIds $productId): int {
        $entityTypeId = self::$db->getProductEntityTypeId();
        $defaultStoreId = self::DEFAULT_STORE_ID;

        $attributeId = self::$db->insert("
            INSERT INTO eav_attribute (entity_type_id, attribute_code, frontend_label, backend_type, frontend_input, is_user_defined) VALUES
                ($entityTypeId, '$attributeCode', '$attributeLabel', 'varchar', 'text', TRUE)
        ");

        self::$db->execute("
            INSERT INTO catalog_eav_attribute (attribute_id, is_visible, is_visible_on_front, used_in_product_listing, is_visible_in_advanced_search) VALUES
                ($attributeId, TRUE, TRUE, TRUE, TRUE)
        ");

        // add attribute to product
        self::$db->insertVarcharProductAttribute($productId, $attributeId, $defaultStoreId, "$attributeCode value for product {$productId->getLinkFieldId()}");

        return $attributeId;
    }

    private function deleteAttribute(int $attributeId): void {
        self::$db->deleteById($attributeId, [
            'catalog_product_entity_varchar' => 'attribute_id',
            'catalog_eav_attribute' => 'attribute_id',
            'eav_attribute' => 'attribute_id'
        ]);
    }
}
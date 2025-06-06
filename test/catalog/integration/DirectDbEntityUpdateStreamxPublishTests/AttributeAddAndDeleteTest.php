<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Indexer\AttributeIndexer;
use StreamX\ConnectorCatalog\Indexer\ProductIndexer;
use StreamX\ConnectorCatalog\test\integration\utils\ConfigurationEditUtils;
use StreamX\ConnectorCatalog\test\integration\utils\EntityIds;

/**
 * @inheritdoc
 */
class AttributeAddAndDeleteTest extends BaseDirectDbEntityUpdateTest {

    const INDEXER_IDS = [AttributeIndexer::INDEXER_ID, ProductIndexer::INDEXER_ID];

    /** @test */
    public function shouldPublishProductThatUsesAttributeAddedDirectlyInDatabase_AndUnpublishAtAttributeDeletion() {
        // given
        $attributeCode = 'the_new_attribute';
        $attributeLabel = 'The New Attribute';
        $productId = self::$db->getProductId('Sprite Foam Roller');

        // and
        $expectedKey = self::productKey($productId);
        $this->removeFromStreamX($expectedKey);

        // when
        ConfigurationEditUtils::addIndexedProductAttributes($attributeCode);
        $attributeId = $this->addAttributeAndAssignToProduct($attributeCode, $attributeLabel, $productId);

        try {
            // and
            $this->reindexMview();

            // then
            $this->assertExactDataIsPublished($expectedKey, 'edited-roller-product.json');
        } finally {
            try {
                // and when
                $this->deleteAttribute($attributeId);
                $this->reindexMview();

                // then
                $this->assertExactDataIsPublished($expectedKey, 'original-roller-product.json');
            } finally {
                ConfigurationEditUtils::restoreDefaultIndexedProductAttributes();
            }
        }
    }

    /**
     * @return int ID of the inserted attribute
     */
    private function addAttributeAndAssignToProduct(string $attributeCode, string $attributeLabel, EntityIds $productId): int {
        $entityTypeId = self::$db->getProductEntityTypeId();
        $attributeId = self::$db->insert("
            INSERT INTO eav_attribute (entity_type_id, attribute_code, frontend_label, backend_type, frontend_input, is_user_defined) VALUES
                ($entityTypeId, '$attributeCode', '$attributeLabel', 'varchar', 'text', TRUE)
        ");

        self::$db->execute("
            INSERT INTO catalog_eav_attribute (attribute_id, is_visible, is_visible_on_front, used_in_product_listing, is_visible_in_advanced_search) VALUES
                ($attributeId, TRUE, TRUE, TRUE, TRUE)
        ");

        // add attribute to product
        self::$db->insertVarcharProductAttribute($productId, $attributeId, "$attributeCode value for product {$productId->getLinkFieldId()}");

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
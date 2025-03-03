<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

/**
 * @inheritdoc
 * @UsesAttributeIndexer
 */
class AttributeUpdateTest extends BaseDirectDbEntityUpdateTest {

    /** @test */
    public function shouldPublishProductThatUsesSimpleAttributeEditedDirectlyInDatabaseToStreamx() {
        $this->shouldPublishProductThatUsesAttributeEditedDirectlyInDatabaseToStreamx(
            'sale',
            'edited-sale-attr-ball-product.json'
        );
    }

    /** @test */
    public function shouldPublishProductThatUsesAttributeWithOptionsEditedDirectlyInDatabaseToStreamx() {
        $this->shouldPublishProductThatUsesAttributeEditedDirectlyInDatabaseToStreamx(
            'material',
            'edited-material-attr-ball-product.json'
        );
    }

    private function shouldPublishProductThatUsesAttributeEditedDirectlyInDatabaseToStreamx(string $attributeCode, string $validationFile): void {
        // given
        $attributeId = self::$db->getProductAttributeId($attributeCode);

        $oldDisplayName = self::$db->getAttributeDisplayName($attributeId);
        $newDisplayName = "Name modified for testing, was $oldDisplayName";

        // and
        $productId = self::$db->getProductId('Dual Handle Cardio Ball'); // this product is known to have both "sale" and "material" attributes
        $expectedKey = self::productKey($productId);
        self::removeFromStreamX($expectedKey);

        // when
        $this->setConfigurationValue($this->PRODUCT_ATTRIBUTES_PATH, 'sale,material');

        $this->renameAttributeInDb($attributeId, $newDisplayName);

        try {
            // and
            $this->reindexMview();

            // then
            $this->assertExactDataIsPublished($expectedKey, $validationFile);
        } finally {
            try {
                $this->renameAttributeInDb($attributeId, $oldDisplayName);
            } finally {
                $this->restoreConfigurationValue($this->PRODUCT_ATTRIBUTES_PATH);
            }
        }
    }

    private function renameAttributeInDb($attributeId, string $newDisplayName): void {
        self::$db->execute("
            UPDATE eav_attribute
               SET frontend_label = '$newDisplayName'
             WHERE attribute_id = $attributeId
        ");
    }
}
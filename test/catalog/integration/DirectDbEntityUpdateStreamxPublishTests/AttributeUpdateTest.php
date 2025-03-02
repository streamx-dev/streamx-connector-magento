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
            'color',
            ['"label": "Color"' => '"label": "Name modified for testing, was Color"']
        );
    }

    /** @test */
    public function shouldPublishProductThatUsesAttributeWithOptionsEditedDirectlyInDatabaseToStreamx() {
        $this->shouldPublishProductThatUsesAttributeEditedDirectlyInDatabaseToStreamx(
            'material',
            ['"label": "Material"' => '"label": "Name modified for testing, was Material"']
        );
    }

    private function shouldPublishProductThatUsesAttributeEditedDirectlyInDatabaseToStreamx(string $attributeCode, array $regexReplacementsForEditedValidationFile): void {
        // given
        $attributeId = self::$db->getProductAttributeId($attributeCode);

        $oldDisplayName = self::$db->getAttributeDisplayName($attributeId);
        $newDisplayName = "Name modified for testing, was $oldDisplayName";

        // and
        $productId = self::$db->getProductId('Sprite Stasis Ball 55 cm'); // this product is known to have both "color" and "material" attributes
        $expectedKey = "default_product:$productId";
        self::removeFromStreamX($expectedKey);

        // when
        $this->setConfigurationValues([
            $this->PRODUCT_ATTRIBUTES_PATH => '', // make sure color and material attributes will always be exported
            $this->EXPORT_PRODUCTS_NOT_VISIBLE_INDIVIDUALLY_PATH => 1 // normally, the product is not visible individually
        ]);

        $this->renameAttributeInDb($attributeId, $newDisplayName);

        try {
            // and
            $this->reindexMview();

            // then
            $this->assertExactDataIsPublished($expectedKey, "edited-ball-product.json", $regexReplacementsForEditedValidationFile);
        } finally {
            try {
                $this->renameAttributeInDb($attributeId, $oldDisplayName);
            } finally {
                $this->restoreConfigurationValues([
                    $this->PRODUCT_ATTRIBUTES_PATH,
                    $this->EXPORT_PRODUCTS_NOT_VISIBLE_INDIVIDUALLY_PATH
                ]);
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
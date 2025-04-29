<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Indexer\AttributeIndexer;
use StreamX\ConnectorCatalog\test\integration\utils\ConfigurationEditUtils;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoEndpointsCaller;

/**
 * @inheritdoc
 */
class AttributeUpdateTest extends BaseAppEntityUpdateTest {

    const INDEXER_IDS = [AttributeIndexer::INDEXER_ID];

    /** @test */
    public function shouldPublishProductThatUsesSimpleAttributeEditedUsingMagentoApplication() {
        $this->shouldPublishProductThatUsesAttributeEditedUsingMagentoApplication(
            'sale',
            'edited-sale-attr-ball-product.json'
        );
    }

    /** @test */
    public function shouldPublishProductThatUsesAttributeWithOptionsEditedUsingMagentoApplication() {
        $this->shouldPublishProductThatUsesAttributeEditedUsingMagentoApplication(
            'material',
            'edited-material-attr-ball-product.json'
        );
    }

    private function shouldPublishProductThatUsesAttributeEditedUsingMagentoApplication(string $attributeCode, string $validationFile): void {
        // given
        $attributeId = self::$db->getProductAttributeId($attributeCode);

        $oldDisplayName = self::$db->getAttributeDisplayName($attributeId);
        $newDisplayName = "Name modified for testing, was $oldDisplayName";

        // and
        $productId = self::$db->getProductId('Dual Handle Cardio Ball'); // this product is known to have both "sale" and "material" attributes
        $expectedKey = self::productKey($productId);
        self::removeFromStreamX($expectedKey);

        // when
        ConfigurationEditUtils::setIndexedProductAttributes('sale', 'material');

        self::renameAttribute($attributeCode, $newDisplayName);

        // then
        try {
            $this->assertExactDataIsPublished($expectedKey, $validationFile);
        } finally {
            try {
                self::renameAttribute($attributeCode, $oldDisplayName);
                $this->assertExactDataIsPublished($expectedKey, "original-ball-product.json");
            } finally {
                ConfigurationEditUtils::restoreDefaultIndexedProductAttributes();
            }
        }
    }

    /** @test */
    public function shouldNotPublishProduct_WhenItsNotIndexedAttribute_WasEditedUsingMagentoApplication(): void {
        // given
        $attributeCode = 'tax_class_id';
        $attributeId = self::$db->getProductAttributeId($attributeCode);
        $oldDisplayName = self::$db->getAttributeDisplayName($attributeId);
        $newDisplayName = "Name modified for testing, was $oldDisplayName";

        // and
        $productId = self::$db->getProductId('Joust Duffle Bag');
        $expectedKey = self::productKey($productId);
        self::removeFromStreamX($expectedKey);

        try {
            // when
            ConfigurationEditUtils::unsetIndexedProductAttribute($attributeCode);
            $this->renameAttribute($attributeId, $newDisplayName);

            // then
            usleep(200_000);
            $this->assertDataIsNotPublished($expectedKey);
        } finally {
            $this->renameAttribute($attributeId, $oldDisplayName);
            ConfigurationEditUtils::restoreDefaultIndexedProductAttributes();
        }
    }

    private function renameAttribute(string $attributeCode, string $newName): void {
        MagentoEndpointsCaller::call('attribute/rename', [
            'attributeCode' => $attributeCode,
            'newName' => $newName
        ]);
    }
}
<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;

/**
 * @inheritdoc
 * @UsesProductIndexer
 */
class MultistoreProductUnpublishTest extends BaseDirectDbEntityUpdateTest {

    /** @test */
    public function shouldUnpublishProductFromStore_WhenProductSwitchedToNotEligibleInThatStore() {
        // given
        $product = self::$db->getProductId('Fusion Backpack');
        $validationFile = 'fusion-backpack-product.json';
        $statusAttributeId = self::$db->getProductAttributeId('status');
        $visibilityAttributeId = self::$db->getProductAttributeId('visibility');

        // and: prepare expected keys
        $keyForStore1 = self::productKey($product, self::STORE_1_CODE);
        $keyForStore2 = self::productKey($product, self::STORE_2_CODE);
        $keyForSecondWebsiteStore = self::productKey($product, self::WEBSITE_2_STORE_CODE);
        $this->removeFromStreamX($keyForStore1, $keyForStore2, $keyForSecondWebsiteStore);

        try {
            // when 1: perform any change in the product, to trigger publishing it from all stores
            self::$db->execute("UPDATE catalog_product_entity SET attribute_set_id = attribute_set_id + 1 WHERE entity_id = {$product->getEntityId()}");
            $this->reindexMview();

            // then
            $this->assertAllPublished([$keyForStore1, $keyForStore2, $keyForSecondWebsiteStore], $validationFile);

            // when 2: disable the product for all stores (using different eligibility settings)
            self::$db->insertIntProductAttribute($product, $statusAttributeId, Status::STATUS_DISABLED, self::$store1Id);
            self::$db->insertIntProductAttribute($product, $visibilityAttributeId, Visibility::VISIBILITY_NOT_VISIBLE, self::$store2Id);
            self::$db->removeProductFromWebsite($product, self::$website2Id);

            $this->reindexMview();

            // then
            $this->assertNonePublished($keyForStore1, $keyForStore2, $keyForSecondWebsiteStore);

            // when 3: enable the products for all stores
            self::$db->deleteIntProductAttribute($product, $statusAttributeId, self::$store1Id);
            self::$db->deleteIntProductAttribute($product, $visibilityAttributeId, self::$store2Id);
            self::$db->addProductToWebsite($product, self::$website2Id);
            $this->reindexMview();

            // then
            $this->assertAllPublished([$keyForStore1, $keyForStore2, $keyForSecondWebsiteStore], $validationFile);
        } finally {
            // restore DB changes performed by the test, in case of any assertion failed
            self::$db->execute("UPDATE catalog_product_entity SET attribute_set_id = attribute_set_id - 1 WHERE entity_id = {$product->getEntityId()}");
            self::$db->deleteIntProductAttribute($product, $statusAttributeId, self::$store1Id);
            self::$db->deleteIntProductAttribute($product, $visibilityAttributeId, self::$store2Id);
            self::$db->addProductToWebsite($product, self::$website2Id);
        }
    }

    private function assertAllPublished(array $expectedKeys, string $validationFile): void {
        foreach ($expectedKeys as $expectedKey) {
            $this->assertExactDataIsPublished($expectedKey, $validationFile);
        }
    }

    private function assertNonePublished(string ...$expectedKeys): void {
        foreach ($expectedKeys as $expectedKey) {
            $this->assertDataIsNotPublished($expectedKey);
        }
    }
}
<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\utils\EntityIds;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoEndpointsCaller;

/**
 * @inheritdoc
 * @UsesProductIndexer
 */
class ProductCategoryUpdateTest extends BaseAppEntityUpdateTest {

    /** @test */
    public function shouldPublishProductCategoryEditedUsingMagentoApplicationToStreamx() {
        // given
        $productName = 'Joust Duffle Bag';
        $productId = self::$db->getProductId($productName);

        $newCategoryName = 'Jackets';
        $newCategoryId = self::$db->getCategoryId($newCategoryName)->getEntityId();

        // read ID (and name) of first category assigned to the product
        $oldCategoryId = self::$db->selectSingleValue("
            SELECT MIN(category_id)
              FROM catalog_category_product
             WHERE product_id = {$productId->getEntityId()}
        ");

        $this->assertNotEquals($newCategoryId, $oldCategoryId);

        // and
        $expectedKey = self::productKey($productId);
        self::removeFromStreamX($expectedKey);

        // when
        self::changeProductCategory($productId, $oldCategoryId, $newCategoryId);

        // then
        try {
            // TODO: when the test is executed for the first time, the actual Json may be missing the "tax_class_id" attribute
            $this->assertExactDataIsPublished($expectedKey, 'bag-with-edited-category.json');
        } finally {
            self::changeProductCategory($productId, $newCategoryId, $oldCategoryId);
            $this->assertExactDataIsPublished($expectedKey, 'original-bag-product.json');
        }
    }

    private function changeProductCategory(EntityIds $productId, int $oldCategoryId, int $newCategoryId): void {
        MagentoEndpointsCaller::call('product/category/change', [
            'productId' => $productId->getEntityId(),
            'oldCategoryId' => $oldCategoryId,
            'newCategoryId' => $newCategoryId
        ]);
    }
}
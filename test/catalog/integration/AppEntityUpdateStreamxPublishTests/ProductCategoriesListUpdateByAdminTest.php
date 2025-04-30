<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCatalog\test\integration\utils\EntityIds;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoEndpointsCaller;

/**
 * @inheritdoc
 * This test verifies publishing a product when Admin adds/removes its categories while editing this product
 */
class ProductCategoriesListUpdateByAdminTest extends BaseAppEntityUpdateTest {

    const INDEXER_IDS = [ProductProcessor::INDEXER_ID];

    /** @test */
    public function shouldPublishProductAddedToAndRemovedFromCategory() {
        // given
        $categoryName = 'Bags';
        $categoryId = self::$db->getCategoryId($categoryName);

        $productToAddToCategory = self::$db->getProductId('Strike Endurance Tee');

        // and
        $expectedProductKey = self::productKey($productToAddToCategory);
        self::removeFromStreamX($expectedProductKey);

        // when
        self::addProductToCategory($categoryId, $productToAddToCategory);

        try {
            // then
            $this->assertExactDataIsPublished($expectedProductKey, 'edited-tee-product.json');
        } finally {
            // and when
            self::removeProductFromCategory($categoryId, $productToAddToCategory);

            // then
            $this->assertExactDataIsPublished($expectedProductKey, 'original-tee-product.json');
        }
    }

    private function addProductToCategory(EntityIds $categoryId, EntityIds $productId): void {
        MagentoEndpointsCaller::call('category/product/add', [
            'categoryId' => $categoryId->getEntityId(),
            'productId' => $productId->getEntityId()
        ]);
    }

    private function removeProductFromCategory(EntityIds $categoryId, EntityIds $productId): void {
        MagentoEndpointsCaller::call('category/product/remove', [
            'categoryId' => $categoryId->getEntityId(),
            'productId' => $productId->getEntityId()
        ]);
    }
}
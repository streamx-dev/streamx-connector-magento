<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\utils\EntityIds;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoEndpointsCaller;

/**
 * @inheritdoc
 * @UsesCategoryIndexer
 */
class CategoryUpdateTest extends BaseAppEntityUpdateTest {

    /** @test */
    public function shouldPublishCategoryEditedUsingMagentoApplication() {
        // given
        $categoryOldName = 'Gear';
        $categoryNewName = 'Gear Articles';
        $categoryId = self::$db->getCategoryId($categoryOldName);

        // and
        $expectedKey = self::categoryKey($categoryId);
        self::removeFromStreamX($expectedKey);

        // when
        self::renameCategory($categoryId, $categoryNewName);

        // then
        try {
            $this->assertExactDataIsPublished($expectedKey, 'edited-gear-category.json');
        } finally {
            self::renameCategory($categoryId, $categoryOldName);
            $this->assertExactDataIsPublished($expectedKey, 'original-gear-category.json');
        }
    }

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

    private function renameCategory(EntityIds $categoryId, string $newName): void {
        MagentoEndpointsCaller::call('category/rename', [
            'categoryId' => $categoryId->getEntityId(),
            'newName' => $newName
        ]);
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
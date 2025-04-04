<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\utils\ConfigurationEditUtils;
use StreamX\ConnectorCatalog\test\integration\utils\ConfigurationKeyPaths;
use StreamX\ConnectorCatalog\test\integration\utils\EntityIds;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoEndpointsCaller;

/**
 * @inheritdoc
 * @UsesCategoryIndexer
 */
class CategoryUpdateTest extends BaseAppEntityUpdateTest {

    /** @test */
    public function shouldPublishCategoryRenamedUsingMagentoApplication() {
        // given
        $defaultName = 'Gear';
        $changedName = 'Gear Articles';
        $categoryId = self::$db->getCategoryId($defaultName);

        // and
        $parentCategoryId = self::$db->getCategoryId('Default Category');
        $editedCategoryKey = self::categoryKey($categoryId);
        $parentCategoryKey = self::categoryKey($parentCategoryId);
        self::removeFromStreamX($editedCategoryKey, $parentCategoryKey);

        // when
        self::renameCategory($categoryId, $changedName);

        try {
            // then
            $this->assertExactDataIsPublished($editedCategoryKey, 'edited-gear-category.json');

            // and
            $this->assertTrue($this->isCurrentlyPublished($parentCategoryKey)); // TODO: do we need to re-publish parent when a category is edited?
        } finally {
            self::renameCategory($categoryId, $defaultName);
            $this->assertExactDataIsPublished($editedCategoryKey, 'original-gear-category.json');
        }
    }

    /** @test */
    public function shouldPublishCategoryAndSubcategoriesWhenUrlKeyIsChangedUsingMagentoApplication() {
        // given
        $categoryName = 'Gear';
        $categoryId = self::$db->getCategoryId($categoryName);
        $defaultUrlKey = self::$db->getVarcharCategoryAttributeValue($categoryId, 'url_key');
        $changedUrlKey = 'super-gear';

        // and
        $parentCategoryId = self::$db->getCategoryId('Default Category');
        $subcategory1Id = self::$db->getCategoryId('Bags');
        $subcategory2Id = self::$db->getCategoryId('Fitness Equipment');
        $subcategory3Id = self::$db->getCategoryId('Watches');

        $editedCategoryKey = self::categoryKey($categoryId);
        $parentCategoryKey = self::categoryKey($parentCategoryId);
        $subcategory1Key = self::categoryKey($subcategory1Id);
        $subcategory2Key = self::categoryKey($subcategory2Id);
        $subcategory3Key = self::categoryKey($subcategory3Id);

        self::removeFromStreamX($editedCategoryKey, $parentCategoryKey, $subcategory1Key, $subcategory2Key, $subcategory3Key);

        // when
        ConfigurationEditUtils::setConfigurationValue(ConfigurationKeyPaths::USE_URL_KEY_AND_ID_TO_GENERATE_SLUG, '1');
        self::changeUrlKeyOfCategory($categoryId, $changedUrlKey);

        try {
            // then
            $this->assertExactDataIsPublished($editedCategoryKey, 'original-gear-category.json', [
                '"slug": "super-gear-3"' => '"slug": "gear-3"'
            ]);

            // and: expect parent and subcategories to also be published, with the new slug of the edited category in their child or parent category data
            $this->assertStringContainsString('"slug":"super-gear-3"', $this->downloadContentAtKey($parentCategoryKey)); // TODO: do we need to re-publish parent when a category is edited?
            $this->assertStringContainsString('"slug":"super-gear-3"', $this->downloadContentAtKey($subcategory1Key));
            $this->assertStringContainsString('"slug":"super-gear-3"', $this->downloadContentAtKey($subcategory2Key));
            $this->assertStringContainsString('"slug":"super-gear-3"', $this->downloadContentAtKey($subcategory3Key));
        } finally {
            self::changeUrlKeyOfCategory($categoryId, $defaultUrlKey);
            ConfigurationEditUtils::restoreConfigurationValue(ConfigurationKeyPaths::USE_URL_KEY_AND_ID_TO_GENERATE_SLUG);
            $this->assertExactDataIsPublished($editedCategoryKey, 'original-gear-category.json');
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

    private function changeUrlKeyOfCategory(EntityIds $categoryId, string $newValue): void {
        MagentoEndpointsCaller::call('category/attribute/change', [
            'categoryId' => $categoryId->getEntityId(),
            'attributeCode' => 'url_key',
            'newValue' => $newValue
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
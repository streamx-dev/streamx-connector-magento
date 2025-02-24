<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

/**
 * @inheritdoc
 * @UsesCategoryIndexer
 */
class MultistoreCategoryAddAndDeleteTest extends BaseDirectDbEntityUpdateTest {

    /** @test */
    public function shouldPublishActiveCategories() {
        // given: insert category as enabled for all stores by default, but disabled for store 1:
        $parentCategoryId = 2;

        $categoryId = $this->insertMultistoreCategory(
            $parentCategoryId,
            [
                self::DEFAULT_STORE_ID => 'Category name',
                self::STORE_1_ID => 'Category name in first store',
                parent::$store2Id => 'Category name in second store'
            ],
            [
                self::DEFAULT_STORE_ID => true,
                self::STORE_1_ID => false,
                parent::$store2Id => true
            ]
        );

        // and
        $expectedKeyForStore1 = "cat:$categoryId";
        $expectedKeyForStore2 = "cat_store_2:$categoryId";
        $this->removeFromStreamX($expectedKeyForStore1, $expectedKeyForStore2);

        try {
            // when
            $this->reindexMview();

            // then
            $this->assertExactDataIsPublished($expectedKeyForStore2, 'added-category.json', [
                // provide values for placeholders in the validation file
                123456789 => $categoryId,
                'CATEGORY_NAME' => 'Category name in second store',
                'CATEGORY_SLUG' => "category-name-in-second-store-$categoryId"
            ]);

            // and
            $this->assertDataIsNotPublished($expectedKeyForStore1);
        } finally {
            // and when
            $this->deleteCategory($categoryId);
            $this->reindexMview();

            // then
            $this->assertDataIsUnpublished($expectedKeyForStore2);
        }
    }

    /** @test */
    public function shouldPublishCategoriesAssignedToStore() {
        // given: switch store with ID 2 to use a new category as its root category
        $rootCategoryIdForStore1 = 2; // this is the default root category for stores
        $rootCategoryIdForStore2 = $this->insertRootCategory('Root category for second store');
        $this->changeRootCategoryForStore(parent::$store2Id, $rootCategoryIdForStore2);

        // and: insert two new categories with different parent category IDs
        $store1CategoryId = $this->insertCategory($rootCategoryIdForStore1, 'Bikes for first store');
        $store2CategoryId = $this->insertCategory($rootCategoryIdForStore2, 'Bikes for second store');

        // and
        $expectedKeyForStore1 = "cat:$store1CategoryId";
        $expectedKeyForStore2 = "cat_store_2:$store2CategoryId";

        $unexpectedKeyForStore1 = "cat:$store2CategoryId";
        $unexpectedKeyForStore2 = "cat_store_2:$store1CategoryId";

        $this->removeFromStreamX($expectedKeyForStore1, $expectedKeyForStore2, $unexpectedKeyForStore1, $unexpectedKeyForStore2);

        try {
            // when
            $this->reindexMview();

            // then
            $this->assertExactDataIsPublished($expectedKeyForStore1, 'added-category.json', [
                // provide values for placeholders in the validation file
                123456789 => $store1CategoryId,
                'CATEGORY_NAME' => 'Bikes for first store',
                'CATEGORY_SLUG' => "bikes-for-first-store-$store1CategoryId"
            ]);
            $this->assertDataIsNotPublished($unexpectedKeyForStore1);

            $this->assertExactDataIsPublished($expectedKeyForStore2, 'added-category.json', [
                // provide values for placeholders in the validation file
                123456789 => $store2CategoryId,
                'CATEGORY_NAME' => 'Bikes for second store',
                'CATEGORY_SLUG' => "bikes-for-second-store-$store2CategoryId",
                // expect different parent category than default
                '"id": 2,' => '"id": ' . $rootCategoryIdForStore2 . ',',
                'Default Category' => 'Root category for second store',
                'default-category-2' => 'root-category-for-second-store-' . $rootCategoryIdForStore2
            ]);
            $this->assertDataIsNotPublished($unexpectedKeyForStore2);
        } finally {
            // and when
            $this->deleteCategory($store1CategoryId);
            $this->deleteCategory($store2CategoryId);
            $this->deleteCategory($rootCategoryIdForStore2);
            try {
                $this->reindexMview();

                // then
                $this->assertDataIsUnpublished($expectedKeyForStore1);
                $this->assertDataIsUnpublished($expectedKeyForStore2);
            } finally {
                $this->changeRootCategoryForStore(parent::$store2Id, $rootCategoryIdForStore1);
            }
        }
    }

    private function insertCategory(int $parentCategoryId, string $defaultName): int {
        return $this->insertMultistoreCategory(
            $parentCategoryId,
            [self::DEFAULT_STORE_ID => $defaultName],
            [self::DEFAULT_STORE_ID => true]
        );
    }

    private function insertMultistoreCategory(int $parentCategoryId, array $storeIdCategoryNameMap, array $storeIdCategoryStatusMap): int {
        $defaultStoreId = self::DEFAULT_STORE_ID;
        $rootCategoryId = 1;
        $attributeSetId = self::$db->getDefaultCategoryAttributeSetId();

        $nameAttrId = self::$db->getCategoryAttributeId('name');
        $displayModeAttrId = self::$db->getCategoryAttributeId('display_mode');
        $urlKeyAttrId = self::$db->getCategoryAttributeId('url_key');
        $isActiveAttrId = self::$db->getCategoryAttributeId('is_active');
        $includeInMenuAttrId = self::$db->getCategoryAttributeId('include_in_menu');

        // 1. Create category
        $categoryId = self::$db->insert("
            INSERT INTO catalog_category_entity (attribute_set_id, parent_id, path, position, level, children_count) VALUES
                ($attributeSetId, $parentCategoryId, '', 1, 2, 0)
        ");

        // 2. Update category path
        self::$db->execute("
            UPDATE catalog_category_entity
               SET path = '$rootCategoryId/$parentCategoryId/$categoryId'
             WHERE entity_id = $categoryId
        ");

        // 3. Set basic attributes
        self::$db->executeAll(["
            INSERT INTO catalog_category_entity_varchar (entity_id, attribute_id, store_id, value) VALUES
                ($categoryId, $displayModeAttrId, $defaultStoreId, 'PRODUCTS')
        ", "
            INSERT INTO catalog_category_entity_int (entity_id, attribute_id, store_id, value) VALUES
                ($categoryId, $includeInMenuAttrId, $defaultStoreId, 1)
        "]);

        // 4. Set default and store-scoped names for the category
        foreach ($storeIdCategoryNameMap as $storeId => $categoryName) {
            $categoryInternalName = strtolower(str_replace(' ', '_', $categoryName));
            self::$db->execute("
                INSERT INTO catalog_category_entity_varchar (entity_id, attribute_id, store_id, value) VALUES
                    ($categoryId, $nameAttrId, $storeId, '$categoryName'),
                    ($categoryId, $urlKeyAttrId, $storeId, '$categoryInternalName')
            ");
        }

        // 5. Set default and store-scoped active statuses for the category
        foreach ($storeIdCategoryStatusMap as $storeId => $isCategoryActive) {
            $isActiveValue = $isCategoryActive ? 1 : 0;
            self::$db->execute("
                INSERT INTO catalog_category_entity_int (entity_id, attribute_id, store_id, value) VALUES
                    ($categoryId, $isActiveAttrId, $storeId, $isActiveValue)
            ");
        }

        return $categoryId;
    }

    private function insertRootCategory(string $categoryName): int {
        $defaultStoreId = self::DEFAULT_STORE_ID;
        $rootCategoryId = 1;
        $attributeSetId = self::$db->getDefaultCategoryAttributeSetId();
        $categoryInternalName = strtolower(str_replace(' ', '_', $categoryName));

        $nameAttrId = self::$db->getCategoryAttributeId('name');
        $displayModeAttrId = self::$db->getCategoryAttributeId('display_mode');
        $urlKeyAttrId = self::$db->getCategoryAttributeId('url_key');
        $isActiveAttrId = self::$db->getCategoryAttributeId('is_active');
        $includeInMenuAttrId = self::$db->getCategoryAttributeId('include_in_menu');

        // 1. Create category
        $categoryId = self::$db->insert("
            INSERT INTO catalog_category_entity (attribute_set_id, parent_id, path, position, level, children_count) VALUES
                ($attributeSetId, $rootCategoryId, '', 1, 1, 0)
        ");

        // 2. Update category path
        self::$db->execute("
            UPDATE catalog_category_entity
               SET path = '$rootCategoryId/$categoryId'
             WHERE entity_id = $categoryId
        ");

        // 3. Set attributes
        self::$db->executeAll(["
            INSERT INTO catalog_category_entity_varchar (entity_id, attribute_id, store_id, value) VALUES
                ($categoryId, $displayModeAttrId, $defaultStoreId, 'PRODUCTS'),
                ($categoryId, $nameAttrId, $defaultStoreId, '$categoryName'),
                ($categoryId, $urlKeyAttrId, $defaultStoreId, '$categoryInternalName')
        ", "
            INSERT INTO catalog_category_entity_int (entity_id, attribute_id, store_id, value) VALUES
                ($categoryId, $includeInMenuAttrId, $defaultStoreId, 1),
                ($categoryId, $isActiveAttrId, $defaultStoreId, 1)
        "]);

        return $categoryId;
    }

    private function changeRootCategoryForStore(int $storeId, int $categoryId): void {
        self::$db->execute("
            UPDATE store_group
               SET root_category_id = $categoryId
             WHERE default_store_id = $storeId"
        );
    }

    private function deleteCategory(int $categoryId): void {
        self::$db->executeAll([
            "DELETE FROM catalog_category_entity_int WHERE entity_id = $categoryId",
            "DELETE FROM catalog_category_entity_varchar WHERE entity_id = $categoryId",
            "DELETE FROM catalog_category_entity WHERE entity_id = $categoryId",
        ]);
    }
}
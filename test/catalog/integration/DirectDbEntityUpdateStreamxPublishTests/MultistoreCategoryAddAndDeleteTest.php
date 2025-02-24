<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

/**
 * @inheritdoc
 * @UsesCategoryIndexer
 */
class MultistoreCategoryAddAndDeleteTest extends BaseMultistoreTest {

    /** @test */
    public function shouldPublishActiveCategories() {
        // given: insert category as enabled for all stores by default, but disabled for store 1:
        $parentCategoryId = 2;

        $categoryId = $this->insertMultistoreCategory(
            $parentCategoryId,
            [
                self::DEFAULT_STORE_ID => 'Category name',
                self::STORE_1_ID => 'Category name in first store',
                parent::getStore2Id() => 'Category name in second store'
            ],
            [
                self::DEFAULT_STORE_ID => true,
                self::STORE_1_ID => false,
                parent::getStore2Id() => true
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
        $this->changeRootCategoryForStore(parent::getStore2Id(), $rootCategoryIdForStore2);

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
                $this->changeRootCategoryForStore(parent::getStore2Id(), $rootCategoryIdForStore1);
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

        $nameAttrId = self::attrId('name');
        $displayModeAttrId = self::attrId('display_mode');
        $urlKeyAttrId = self::attrId('url_key');
        $isActiveAttrId = self::attrId('is_active');
        $includeInMenuAttrId = self::attrId('include_in_menu');

        $categoryId = self::$db->insertCategory($parentCategoryId, "$rootCategoryId/$parentCategoryId");

        // 2. Set basic attributes
        self::$db->insertVarcharCategoryAttribute($categoryId, $displayModeAttrId, $defaultStoreId, 'PRODUCTS');
        self::$db->insertIntCategoryAttribute($categoryId, $includeInMenuAttrId, $defaultStoreId, 1);

        // 3. Set default and store-scoped names for the category
        foreach ($storeIdCategoryNameMap as $storeId => $categoryName) {
            $categoryInternalName = strtolower(str_replace(' ', '_', $categoryName));
            self::$db->insertVarcharCategoryAttribute($categoryId, $nameAttrId, $storeId, $categoryName);
            self::$db->insertVarcharCategoryAttribute($categoryId, $urlKeyAttrId, $storeId, $categoryInternalName);
        }

        // 4. Set default and store-scoped active statuses for the category
        foreach ($storeIdCategoryStatusMap as $storeId => $isCategoryActive) {
            self::$db->insertIntCategoryAttribute($categoryId, $isActiveAttrId, $storeId, $isCategoryActive ? 1 : 0);
        }

        return $categoryId;
    }

    private function insertRootCategory(string $categoryName): int {
        $defaultStoreId = self::DEFAULT_STORE_ID;
        $rootCategoryId = 1;

        $categoryInternalName = strtolower(str_replace(' ', '_', $categoryName));

        $nameAttrId = self::attrId('name');
        $displayModeAttrId = self::attrId('display_mode');
        $urlKeyAttrId = self::attrId('url_key');
        $isActiveAttrId = self::attrId('is_active');
        $includeInMenuAttrId = self::attrId('include_in_menu');

        // 1. Create category
        $categoryId = self::$db->insertCategory($rootCategoryId, $rootCategoryId);

        // 2. Set attributes
        self::$db->insertVarcharCategoryAttribute($categoryId, $displayModeAttrId, $defaultStoreId, 'PRODUCTS');
        self::$db->insertVarcharCategoryAttribute($categoryId, $nameAttrId, $defaultStoreId, $categoryName);
        self::$db->insertVarcharCategoryAttribute($categoryId, $urlKeyAttrId, $defaultStoreId, $categoryInternalName);
        self::$db->insertIntCategoryAttribute($categoryId, $includeInMenuAttrId, $defaultStoreId, 1);
        self::$db->insertIntCategoryAttribute($categoryId, $isActiveAttrId, $defaultStoreId, 1);

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
        self::$db->deleteAll($categoryId, [
            'catalog_category_entity_int' => self::$db->getEntityAttributeLinkField(),
            'catalog_category_entity_varchar' => self::$db->getEntityAttributeLinkField(),
            'catalog_category_entity' => 'entity_id'
        ]);
    }

    private static function attrId($attrCode): string {
        return self::$db->getCategoryAttributeId($attrCode);
    }
}
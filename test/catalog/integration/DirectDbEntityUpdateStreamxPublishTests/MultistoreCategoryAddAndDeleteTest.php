<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\Indexer\CategoryProcessor;

/**
 * @inheritdoc
 */
class MultistoreCategoryAddAndDeleteTest extends BaseMultistoreTest {

    protected function indexerName(): string {
        return CategoryProcessor::INDEXER_ID;
    }

    /** @test */
    public function shouldPublishCategoryAddedDirectlyInDatabaseToStreamx_AndUnpublishDeletedCategory() {
        // given: insert category as enabled for all stores by default, but disabled for store 1:
        $categoryId = $this->insertCategory(
            [
                self::DEFAULT_STORE_ID => 'Category name',
                self::STORE_1_ID => 'Category name in first store',
                self::STORE_2_ID => 'Category name in second store'
            ],
            [
                self::DEFAULT_STORE_ID => true,
                self::STORE_1_ID => false,
                self::STORE_2_ID => true
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

    private function insertCategory(array $storeIdCategoryNameMap, array $storeIdCategoryStatusMap): int {
        $defaultStoreId = self::DEFAULT_STORE_ID;
        $rootCategoryId = 1;
        $parentCategoryId = 2;
        $attributeSetId = $this->db->getDefaultCategoryAttributeSetId();

        $nameAttrId = $this->db->getCategoryAttributeId('name');
        $displayModeAttrId = $this->db->getCategoryAttributeId('display_mode');
        $urlKeyAttrId = $this->db->getCategoryAttributeId('url_key');
        $isActiveAttrId = $this->db->getCategoryAttributeId('is_active');
        $includeInMenuAttrId = $this->db->getCategoryAttributeId('include_in_menu');

        // 1. Create category
        $categoryId = $this->db->insert("
            INSERT INTO catalog_category_entity (attribute_set_id, parent_id, path, position, level, children_count) VALUES
                ($attributeSetId, $parentCategoryId, '', 1, 2, 0)
        ");

        // 2. Update category path
        $this->db->execute("
            UPDATE catalog_category_entity
               SET path = '$rootCategoryId/$parentCategoryId/$categoryId'
             WHERE entity_id = $categoryId
        ");

        // 3. Set basic attributes
        $this->db->executeAll(["
            INSERT INTO catalog_category_entity_varchar (entity_id, attribute_id, store_id, value) VALUES
                ($categoryId, $displayModeAttrId, $defaultStoreId, 'PRODUCTS')
        ", "
            INSERT INTO catalog_category_entity_int (entity_id, attribute_id, store_id, value) VALUES
                ($categoryId, $includeInMenuAttrId, $defaultStoreId, 1)
        "]);

        // 4. Set default and store-scoped names for the category
        foreach ($storeIdCategoryNameMap as $storeId => $categoryName) {
            $categoryInternalName = strtolower(str_replace(' ', '_', $categoryName));
            $this->db->execute("
                INSERT INTO catalog_category_entity_varchar (entity_id, attribute_id, store_id, value) VALUES
                    ($categoryId, $nameAttrId, $storeId, '$categoryName'),
                    ($categoryId, $urlKeyAttrId, $storeId, '$categoryInternalName')
            ");
        }

        // 5. Set default and store-scoped active statuses for the category
        foreach ($storeIdCategoryStatusMap as $storeId => $isCategoryActive) {
            $isActiveValue = $isCategoryActive ? 1 : 0;
            $this->db->execute("
                INSERT INTO catalog_category_entity_int (entity_id, attribute_id, store_id, value) VALUES
                    ($categoryId, $isActiveAttrId, $storeId, $isActiveValue)
            ");
        }

        return $categoryId;
    }

    private function deleteCategory(int $categoryId): void {
        $this->db->executeAll([
            "DELETE FROM catalog_category_entity_int WHERE entity_id = $categoryId",
            "DELETE FROM catalog_category_entity_varchar WHERE entity_id = $categoryId",
            "DELETE FROM catalog_category_entity WHERE entity_id = $categoryId",
        ]);
    }
}
<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

/**
 * @inheritdoc
 * @UsesCategoryIndexer
 */
class CategoryAddAndDeleteTest extends BaseDirectDbEntityUpdateTest {

    /** @test */
    public function shouldPublishCategoryAddedDirectlyInDatabaseToStreamx_AndUnpublishDeletedCategory() {
        // given
        $categoryName = 'The new Category';

        // when
        $categoryId = $this->insertNewCategory($categoryName);
        $expectedKey = "cat:$categoryId";

        try {
            // and
            $this->reindexMview();

            // then
            $this->assertExactDataIsPublished($expectedKey, 'added-category.json', [
                // provide values for placeholders in the validation file
                123456789 => $categoryId,
                'CATEGORY_NAME' => 'The new Category',
                'CATEGORY_SLUG' => "the-new-category-$categoryId"
            ]);
        } finally {
            // and when
            $this->deleteCategory($categoryId);
            $this->reindexMview();

            // then
            $this->assertDataIsUnpublished($expectedKey);
        }
    }

    /**
     * Inserts new category to database
     * @return int ID of the inserted category
     */
    private function insertNewCategory(string $categoryName): int {
        $categoryInternalName = strtolower(str_replace(' ', '_', $categoryName));
        $rootCategoryId = 1;
        $parentCategoryId = 2;
        $defaultStoreId = 0;

        $categoryId = self::$db->insertCategory($parentCategoryId, "$rootCategoryId/$parentCategoryId");
        self::$db->insertVarcharCategoryAttribute($categoryId,  self::attrId('name'), $defaultStoreId, $categoryName);
        self::$db->insertVarcharCategoryAttribute($categoryId, self::attrId('display_mode'), $defaultStoreId, 'PRODUCTS');
        self::$db->insertVarcharCategoryAttribute($categoryId, self::attrId('url_key'), $defaultStoreId, $categoryInternalName);
        self::$db->insertIntCategoryAttribute($categoryId, self::attrId('is_active'), $defaultStoreId, TRUE);
        self::$db->insertIntCategoryAttribute($categoryId, self::attrId('include_in_menu'), $defaultStoreId, TRUE);

        return $categoryId;
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
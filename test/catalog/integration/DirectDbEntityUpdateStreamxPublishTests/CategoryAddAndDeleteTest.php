<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\utils\EntityIds;

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
        $category = $this->insertNewCategory($categoryName);
        $expectedKey = self::categoryKey($category);

        try {
            // and
            $this->reindexMview();

            // then
            $this->assertExactDataIsPublished($expectedKey, 'added-category.json', [
                // provide values for placeholders in the validation file
                123456789 => $category->getEntityId(),
                'CATEGORY_NAME' => 'The new Category',
                'CATEGORY_SLUG' => "the-new-category-{$category->getEntityId()}"
            ]);
        } finally {
            // and when
            $this->deleteCategory($category);
            $this->reindexMview();

            // then
            $this->assertDataIsUnpublished($expectedKey);
        }
    }

    private function insertNewCategory(string $categoryName): EntityIds {
        $categoryInternalName = strtolower(str_replace(' ', '_', $categoryName));
        $rootCategoryId = 1;
        $parentCategoryId = 2;
        $defaultStoreId = self::DEFAULT_STORE_ID;

        $category = self::$db->insertCategory($parentCategoryId, "$rootCategoryId/$parentCategoryId");

        self::$db->insertVarcharCategoryAttribute($category,  self::attrId('name'), $defaultStoreId, $categoryName);
        self::$db->insertVarcharCategoryAttribute($category, self::attrId('url_key'), $defaultStoreId, $categoryInternalName);
        self::$db->insertIntCategoryAttribute($category, self::attrId('is_active'), $defaultStoreId, TRUE);

        return $category;
    }

    static function deleteCategory(EntityIds $categoryIds): void {
        self::$db->deleteById($categoryIds->getLinkFieldId(), [
            'catalog_category_entity_int' => self::$db->getEntityAttributeLinkField(),
            'catalog_category_entity_varchar' => self::$db->getEntityAttributeLinkField()
        ]);
        self::$db->deleteById($categoryIds->getEntityId(), [
            'catalog_category_entity' => 'entity_id'
        ]);
        if (self::$db->isEnterpriseMagento()) {
            self::$db->deleteById($categoryIds->getEntityId(), [
                'sequence_catalog_category' => 'sequence_value'
            ]);
        }
    }

    private static function attrId($attrCode): string {
        return self::$db->getCategoryAttributeId($attrCode);
    }
}
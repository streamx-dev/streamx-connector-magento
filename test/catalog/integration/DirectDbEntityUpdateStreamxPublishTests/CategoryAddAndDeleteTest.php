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
        $attributeSetId = self::$db->getDefaultCategoryAttributeSetId();

        $categoryId = self::$db->insert("
            INSERT INTO catalog_category_entity (attribute_set_id, parent_id, path, position, level, children_count) VALUES
                ($attributeSetId, $parentCategoryId, '', 1, 2, 0)
        ");

        self::$db->execute("
            UPDATE catalog_category_entity
               SET path = '$rootCategoryId/$parentCategoryId/$categoryId'
             WHERE entity_id = $categoryId
        ");

        self::$db->execute("
            INSERT INTO catalog_category_entity_varchar (entity_id, attribute_id, store_id, value) VALUES
                ($categoryId, " . $this->attrId('name') . ", $defaultStoreId, '$categoryName'),
                ($categoryId, " . $this->attrId('display_mode') . ", $defaultStoreId, 'PRODUCTS'),
                ($categoryId, " . $this->attrId('url_key') . ", $defaultStoreId, '$categoryInternalName')
        ");

        self::$db->execute("
            INSERT INTO catalog_category_entity_int (entity_id, attribute_id, store_id, value) VALUES
                ($categoryId, " . $this->attrId('is_active') . ", $defaultStoreId, TRUE),
                ($categoryId, " . $this->attrId('include_in_menu') . ", $defaultStoreId, TRUE)
        ");

        return $categoryId;
    }

    private function deleteCategory(int $categoryId): void {
        self::$db->executeAll([
            "DELETE FROM catalog_category_entity_int WHERE entity_id = $categoryId",
            "DELETE FROM catalog_category_entity_varchar WHERE entity_id = $categoryId",
            "DELETE FROM catalog_category_entity WHERE entity_id = $categoryId",
        ]);
    }

    private function attrId($attrCode): string {
        return self::$db->getCategoryAttributeId($attrCode);
    }
}
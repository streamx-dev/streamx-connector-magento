<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\Indexer\CategoryProcessor;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoMySqlQueryExecutor as DB;

/**
 * @inheritdoc
 */
class CategoryAddAndDeleteTest extends BaseDirectDbEntityUpdateTest {

    protected function indexerName(): string {
        return CategoryProcessor::INDEXER_ID;
    }

    /** @test */
    public function shouldPublishCategoryAddedUsingMagentoApplicationToStreamx_AndUnpublishDeletedCategory() {
        // given
        $categoryName = 'The new Category';

        // when
        $categoryId = self::insertNewCategory($categoryName);
        $this->reindexMview();

        // then
        $expectedKey = "category_$categoryId";
        try {
            $this->assertDataIsPublished($expectedKey, $categoryName);
        } finally {
            // and when
            self::deleteCategory($categoryId);
            $this->reindexMview();

            // then
            $this->assertDataIsUnpublished($expectedKey);
        }
    }

    /**
     * Inserts new category to database
     * @return int ID of the inserted category
     */
    private static function insertNewCategory(string $categoryName): int {
        $categoryInternalName = strtolower(str_replace(' ', '_', $categoryName));
        $rootCategoryId = 1;
        $parentCategoryId = 2;
        $defaultStoreId = 0;
        $attributeSetId = DB::getDefaultProductAttributeSetId();

        DB::execute("
            INSERT INTO catalog_category_entity (attribute_set_id, parent_id, path, position, level, children_count) VALUES
                ($attributeSetId, $parentCategoryId, '', 1, 2, 0)
        ");

        $categoryId = DB::selectFirstField("
            SELECT MAX(entity_id)
              FROM catalog_category_entity
        ");

        DB::execute("
            UPDATE catalog_category_entity
               SET path = '$rootCategoryId/$parentCategoryId/$categoryId'
             WHERE entity_id = $categoryId
        ");

        DB::execute("
            INSERT INTO catalog_category_entity_varchar (entity_id, attribute_id, store_id, value) VALUES
                ($categoryId, " . self::attrId('name') . ", $defaultStoreId, '$categoryName'),
                ($categoryId, " . self::attrId('display_mode') . ", $defaultStoreId, 'PRODUCTS'),
                ($categoryId, " . self::attrId('url_key') . ", $defaultStoreId, '$categoryInternalName')
        ");

        DB::execute("
            INSERT INTO catalog_category_entity_int (entity_id, attribute_id, store_id, value) VALUES
                ($categoryId, " . self::attrId('is_active') . ", $defaultStoreId, TRUE),
                ($categoryId, " . self::attrId('include_in_menu') . ", $defaultStoreId, TRUE)
        ");

        return $categoryId;
    }

    private static function deleteCategory(int $categoryId): void {
        DB::executeAll([
            "DELETE FROM catalog_category_entity_int WHERE entity_id = $categoryId",
            "DELETE FROM catalog_category_entity_varchar WHERE entity_id = $categoryId",
            "DELETE FROM catalog_category_entity WHERE entity_id = $categoryId",
        ]);
    }

    private static function attrId($attrCode): string {
        return DB::getCategoryAttributeId($attrCode);
    }
}
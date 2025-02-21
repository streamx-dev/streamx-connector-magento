<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

/**
 * @inheritdoc
 * @UsesCategoryIndexer
 */
class CategoryUpdateTest extends BaseDirectDbEntityUpdateTest {

    /** @test */
    public function shouldPublishCategoryEditedDirectlyInDatabaseToStreamx() {
        // given
        $categoryOldName = 'Gear';
        $categoryNewName = 'Gear Articles';
        $categoryId = self::$db->getCategoryId($categoryOldName);

        // and
        $expectedKey = "cat:$categoryId";
        self::removeFromStreamX($expectedKey);

        // when
        $this->renameCategoryInDb($categoryId, $categoryNewName);

        try {
            // and
            $this->reindexMview();

            // then
            $this->assertExactDataIsPublished($expectedKey, 'edited-gear-category.json');
        } finally {
            $this->renameCategoryInDb($categoryId, $categoryOldName);
        }
    }

    private function renameCategoryInDb(int $categoryId, string $newName): void {
        $categoryNameAttributeId = self::$db->getCategoryNameAttributeId();
        self::$db->execute("
            UPDATE catalog_category_entity_varchar
               SET value = '$newName'
             WHERE attribute_id = $categoryNameAttributeId
               AND entity_id = $categoryId
        ");
    }
}
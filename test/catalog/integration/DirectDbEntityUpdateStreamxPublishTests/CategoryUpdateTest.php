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
        self::$db->renameCategory($categoryId, $categoryNewName);

        try {
            // and
            $this->reindexMview();

            // then
            $this->assertExactDataIsPublished($expectedKey, 'edited-gear-category.json');
        } finally {
            self::$db->renameCategory($categoryId, $categoryOldName);
        }
    }
}
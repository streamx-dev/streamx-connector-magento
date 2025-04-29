<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Indexer\CategoryIndexer;

/**
 * @inheritdoc
 */
class CategoryUpdateTest extends BaseDirectDbEntityUpdateTest {

    const INDEXER_IDS = [CategoryIndexer::INDEXER_ID];

    /** @test */
    public function shouldPublishCategoryEditedDirectlyInDatabase() {
        // given
        $categoryOldName = 'Gear';
        $categoryNewName = 'Gear Articles';
        $categoryId = self::$db->getCategoryId($categoryOldName);

        // and
        $expectedKey = self::categoryKey($categoryId);
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
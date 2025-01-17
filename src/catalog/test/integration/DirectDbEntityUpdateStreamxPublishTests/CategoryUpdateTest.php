<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\Indexer\CategoryProcessor;

/**
 * @inheritdoc
 */
class CategoryUpdateTest extends BaseDirectDbEntityUpdateTest {

    protected function indexerName(): string {
        return CategoryProcessor::INDEXER_ID;
    }

    /** @test */
    public function shouldPublishCategoryEditedDirectlyInDatabaseToStreamx() {
        // given
        $categoryOldName = 'Gear';
        $categoryNewName = 'Gear Articles';
        $categoryId = $this->db->getCategoryId($categoryOldName);

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
        $categoryNameAttributeId = $this->db->getCategoryNameAttributeId();
        $this->db->execute("
            UPDATE catalog_category_entity_varchar
               SET value = '$newName'
             WHERE attribute_id = $categoryNameAttributeId
               AND entity_id = $categoryId
        ");
    }
}
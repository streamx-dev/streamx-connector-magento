<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\Indexer\CategoryProcessor;
use function date;

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
        $categoryOldName = 'Watches';
        $categoryNewName = 'Name modified for testing, at ' . date("Y-m-d H:i:s");
        $categoryId = $this->db->getCategoryId($categoryOldName);

        // and
        $expectedKey = "category_$categoryId";
        self::removeFromStreamX($expectedKey);

        // when
        $this->renameCategoryInDb($categoryId, $categoryNewName);

        try {
            // and
            $this->reindexMview();

            // then
            $this->assertDataIsPublished($expectedKey, $categoryNewName);
        } finally {
            $this->renameCategoryInDb($categoryId, $categoryOldName);
        }
    }

    private function renameCategoryInDb(int $categoryId, string $newName) {
        $categoryNameAttributeId = $this->db->getCategoryNameAttributeId();
        $this->db->execute("
            UPDATE catalog_category_entity_varchar
               SET value = '$newName'
             WHERE attribute_id = $categoryNameAttributeId
               AND entity_id = $categoryId
        ");
    }
}
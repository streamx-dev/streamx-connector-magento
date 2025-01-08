<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\Indexer\CategoryProcessor;
use function date;

/**
 * @inheritdoc
 */
class CategoryUpdateTest extends BaseAppEntityUpdateTest {

    protected function indexerName(): string {
        return CategoryProcessor::INDEXER_ID;
    }

    /** @test */
    public function shouldPublishCategoryEditedUsingMagentoApplicationToStreamx() {
        // given
        $categoryOldName = 'Watches';
        $categoryNewName = 'Name modified for testing, at ' . date("Y-m-d H:i:s");
        $categoryId = $this->db->getCategoryId($categoryOldName);

        // and
        $expectedKey = "category_$categoryId";
        self::removeFromStreamX($expectedKey);

        // when
        self::renameCategory($categoryId, $categoryNewName);

        // then
        try {
            $this->assertDataIsPublished($expectedKey, $categoryNewName);
        } finally {
            self::renameCategory($categoryId, $categoryOldName);
            $this->assertDataIsPublished($expectedKey, $categoryOldName);
        }
    }

    private function renameCategory(int $categoryId, string $newName) {
        $this->callMagentoEndpoint('category/rename', [
            'categoryId' => $categoryId,
            'newName' => $newName
        ]);
    }
}
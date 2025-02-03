<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests\BaseAppEntityUpdateTest;
use StreamX\ConnectorCatalog\Model\Indexer\CategoryProcessor;

/**
 * @inheritdoc
 */
class CategoryAddAndDeleteTest extends BaseAppEntityUpdateTest {

    protected function indexerName(): string {
        return CategoryProcessor::INDEXER_ID;
    }

    /** @test */
    public function shouldPublishCategoryAddedUsingMagentoApplicationToStreamx_AndUnpublishDeletedCategory() {
        // given
        $categoryName = 'The new Category';

        // when
        $categoryId = self::addCategory($categoryName);

        // then
        $expectedKey = "cat:$categoryId";
        try {
            $this->assertDataIsPublished($expectedKey, $categoryName);
        } finally {
            // and when
            self::deleteCategory($categoryId);

            // then
            $this->assertDataIsUnpublished($expectedKey);
        }
    }

    private function addCategory(string $categoryName): int {
        return (int) $this->callMagentoPutEndpoint('category/add', [
            'categoryName' => $categoryName
        ]);
    }

    private function deleteCategory(int $categoryId): void {
        $this->callMagentoPutEndpoint('category/delete', [
            'categoryId' => $categoryId
        ]);
    }
}
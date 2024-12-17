<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

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
        $expectedKey = "category_$categoryId";
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
        return (int) $this->callMagentoEndpoint('category/add', [
            'categoryName' => $categoryName
        ]);
    }

    private function deleteCategory(int $categoryId): void {
        $this->callMagentoEndpoint('category/delete', [
            'categoryId' => $categoryId
        ]);
    }
}
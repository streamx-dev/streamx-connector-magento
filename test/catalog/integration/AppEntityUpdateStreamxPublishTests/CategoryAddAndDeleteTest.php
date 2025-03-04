<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

/**
 * @inheritdoc
 * @UsesCategoryIndexer
 */
class CategoryAddAndDeleteTest extends BaseAppEntityUpdateTest {

    /** @test */
    public function shouldPublishCategoryAddedUsingMagentoApplicationToStreamx_AndUnpublishDeletedCategory() {
        // given
        $categoryName = 'The new Category';

        // when
        $categoryId = self::addCategory($categoryName);

        // then
        $expectedKey = self::categoryKeyFromEntityId($categoryId);
        try {
            $this->assertExactDataIsPublished($expectedKey, 'added-category.json', [
                // provide values for placeholders in the validation file
                '"id": ' . $categoryId => '"id": 123456789',
                'The new Category' => 'CATEGORY_NAME',
                "the-new-category-$categoryId" => 'CATEGORY_SLUG'
            ]);
        } finally {
            // and when
            self::deleteCategory($categoryId);

            // then
            $this->assertDataIsUnpublished($expectedKey);
        }
    }

    private function addCategory(string $categoryName): int {
        return (int) self::callMagentoPutEndpoint('category/add', [
            'categoryName' => $categoryName
        ]);
    }

    private function deleteCategory(int $categoryId): void {
        self::callMagentoPutEndpoint('category/delete', [
            'categoryId' => $categoryId
        ]);
    }
}
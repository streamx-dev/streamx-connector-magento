<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\Indexer\CategoryProcessor;
use StreamX\ConnectorCatalog\test\integration\utils\CodeCoverageReportGenerator;

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
        $categoryNewName = 'Name modified for testing';
        $categoryId = $this->db->getCategoryId($categoryOldName);

        // and
        $expectedKey = "category_$categoryId";
        self::removeFromStreamX($expectedKey);

        // when
        self::renameCategory($categoryId, $categoryNewName);

        // then
        try {
            $this->assertExactDataIsPublished($expectedKey,
                '{
                    "parent_id": 3,
                    "path": "1\/2\/3\/6",
                    "position": 3,
                    "level": 3,
                    "children_count": 0,
                    "name": "Name modified for testing",
                    "url_key": "name-modified-for-testing-6",
                    "url_path": "gear\/watches.html",
                    "is_active": true,
                    "is_anchor": true,
                    "include_in_menu": 1,
                    "children": null,
                    "id": 6,
                    "slug": "name-modified-for-testing-6",
                    "default_sort_by": "position",
                    "available_sort_by": [
                        "name",
                        "price",
                        "position"
                    ],
                    "product_count": 9,
                    "children_data": []
                }'
            );
        } finally {
            self::renameCategory($categoryId, $categoryOldName);
            $this->assertExactDataIsPublished($expectedKey,
                '{
                    "parent_id": 3,
                    "path": "1\/2\/3\/6",
                    "position": 3,
                    "level": 3,
                    "children_count": 0,
                    "name": "Watches",
                    "url_key": "watches-6",
                    "url_path": "gear\/watches.html",
                    "is_active": true,
                    "is_anchor": true,
                    "include_in_menu": 1,
                    "children": null,
                    "id": 6,
                    "slug": "watches-6",
                    "default_sort_by": "position",
                    "available_sort_by": [
                        "name",
                        "price",
                        "position"
                    ],
                    "product_count": 9,
                    "children_data": []
                }'
            );
        }
    }

    private function renameCategory(int $categoryId, string $newName) {
        $coverage = $this->callMagentoPutEndpoint('category/rename', [
            'categoryId' => $categoryId,
            'newName' => $newName
        ]);

        if (getenv('GENERATE_CODE_COVERAGE_REPORT') === 'true') {
            CodeCoverageReportGenerator::generateCodeCoverageReport($coverage, $this);
        }
    }
}
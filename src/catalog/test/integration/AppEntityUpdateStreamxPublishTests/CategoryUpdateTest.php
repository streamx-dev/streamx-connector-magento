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
        $categoryOldName = 'Gear';
        $categoryNewName = 'Gear Articles';
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
                    "parent_id": 2,
                    "path": "1\/2\/3",
                    "position": 4,
                    "level": 2,
                    "children_count": 3,
                    "name": "Gear Articles",
                    "display_mode": "PAGE",
                    "url_key": "gear-articles-3",
                    "url_path": "gear.html",
                    "is_active": true,
                    "is_anchor": false,
                    "include_in_menu": 1,
                    "children": "4,5,6",
                    "id": 3,
                    "slug": "gear-articles-3",
                    "default_sort_by": "position",
                    "available_sort_by": [
                        "name",
                        "price",
                        "position"
                    ],
                    "product_count": 46,
                    "children_data": [
                        {
                            "parent_id": 3,
                            "path": "1\/2\/3\/4",
                            "position": 1,
                            "level": 3,
                            "children_count": 0,
                            "children_data": [],
                            "name": "Bags",
                            "url_key": "bags-4",
                            "url_path": "gear\/bags.html",
                            "is_active": true,
                            "is_anchor": true,
                            "include_in_menu": 1,
                            "product_count": 14,
                            "id": 4,
                            "slug": "bags-4"
                        },
                        {
                            "parent_id": 3,
                            "path": "1\/2\/3\/5",
                            "position": 2,
                            "level": 3,
                            "children_count": 0,
                            "children_data": [],
                            "name": "Fitness Equipment",
                            "url_key": "fitness-equipment-5",
                            "url_path": "gear\/fitness-equipment.html",
                            "is_active": true,
                            "is_anchor": true,
                            "include_in_menu": 1,
                            "product_count": 23,
                            "id": 5,
                            "slug": "fitness-equipment-5"
                        },
                        {
                            "parent_id": 3,
                            "path": "1\/2\/3\/6",
                            "position": 3,
                            "level": 3,
                            "children_count": 0,
                            "children_data": [],
                            "name": "Watches",
                            "url_key": "watches-6",
                            "url_path": "gear\/watches.html",
                            "is_active": true,
                            "is_anchor": true,
                            "include_in_menu": 1,
                            "product_count": 9,
                            "id": 6,
                            "slug": "watches-6"
                        }
                    ]
                }'
            );
        } finally {
            self::renameCategory($categoryId, $categoryOldName);
            $this->assertExactDataIsPublished($expectedKey,
                '{
                    "parent_id": 2,
                    "path": "1\/2\/3",
                    "position": 4,
                    "level": 2,
                    "children_count": 3,
                    "name": "Gear",
                    "display_mode": "PAGE",
                    "url_key": "gear-3",
                    "url_path": "gear.html",
                    "is_active": true,
                    "is_anchor": false,
                    "include_in_menu": 1,
                    "children": "4,5,6",
                    "id": 3,
                    "slug": "gear-3",
                    "default_sort_by": "position",
                    "available_sort_by": [
                        "name",
                        "price",
                        "position"
                    ],
                    "product_count": 46,
                    "children_data": [
                        {
                            "parent_id": 3,
                            "path": "1\/2\/3\/4",
                            "position": 1,
                            "level": 3,
                            "children_count": 0,
                            "children_data": [],
                            "name": "Bags",
                            "url_key": "bags-4",
                            "url_path": "gear\/bags.html",
                            "is_active": true,
                            "is_anchor": true,
                            "include_in_menu": 1,
                            "product_count": 14,
                            "id": 4,
                            "slug": "bags-4"
                        },
                        {
                            "parent_id": 3,
                            "path": "1\/2\/3\/5",
                            "position": 2,
                            "level": 3,
                            "children_count": 0,
                            "children_data": [],
                            "name": "Fitness Equipment",
                            "url_key": "fitness-equipment-5",
                            "url_path": "gear\/fitness-equipment.html",
                            "is_active": true,
                            "is_anchor": true,
                            "include_in_menu": 1,
                            "product_count": 23,
                            "id": 5,
                            "slug": "fitness-equipment-5"
                        },
                        {
                            "parent_id": 3,
                            "path": "1\/2\/3\/6",
                            "position": 3,
                            "level": 3,
                            "children_count": 0,
                            "children_data": [],
                            "name": "Watches",
                            "url_key": "watches-6",
                            "url_path": "gear\/watches.html",
                            "is_active": true,
                            "is_anchor": true,
                            "include_in_menu": 1,
                            "product_count": 9,
                            "id": 6,
                            "slug": "watches-6"
                        }
                    ]
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
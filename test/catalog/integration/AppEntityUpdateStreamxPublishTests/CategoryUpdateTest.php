<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\utils\CodeCoverageReportGenerator;

/**
 * @inheritdoc
 * @UsesCategoryIndexer
 */
class CategoryUpdateTest extends BaseAppEntityUpdateTest {

    /** @test */
    public function shouldPublishCategoryEditedUsingMagentoApplicationToStreamx() {
        // given
        $categoryOldName = 'Gear';
        $categoryNewName = 'Gear Articles';
        $categoryId = self::$db->getCategoryId($categoryOldName);

        // and
        $expectedKey = "default_category:$categoryId";
        self::removeFromStreamX($expectedKey);

        // when
        self::renameCategory($categoryId, $categoryNewName);

        // then
        try {
            $this->assertExactDataIsPublished($expectedKey, 'edited-gear-category.json');
        } finally {
            self::renameCategory($categoryId, $categoryOldName);
            $this->assertExactDataIsPublished($expectedKey, 'original-gear-category.json');
        }
    }

    private function renameCategory(int $categoryId, string $newName): void {
        $coverage = self::callMagentoPutEndpoint('category/rename', [
            'categoryId' => $categoryId,
            'newName' => $newName
        ]);

        if (getenv('GENERATE_CODE_COVERAGE_REPORT') === 'true') {
            CodeCoverageReportGenerator::generateCodeCoverageReport($coverage, $this);
        }
    }
}
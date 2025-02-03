<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests\BaseAppEntityUpdateTest;
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
        $expectedKey = "cat:$categoryId";
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
        $coverage = $this->callMagentoPutEndpoint('category/rename', [
            'categoryId' => $categoryId,
            'newName' => $newName
        ]);

        if (getenv('GENERATE_CODE_COVERAGE_REPORT') === 'true') {
            CodeCoverageReportGenerator::generateCodeCoverageReport($coverage, $this);
        }
    }
}
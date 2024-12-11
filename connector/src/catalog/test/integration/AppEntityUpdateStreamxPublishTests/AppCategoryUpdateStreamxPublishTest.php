<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\Indexer\CategoryProcessor;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoMySqlQueryExecutor;
use function date;

/**
 * @inheritdoc
 */
class AppCategoryUpdateStreamxPublishTest extends BaseAppEntityUpdateStreamxPublishTest {

    protected function indexerName(): string {
        return CategoryProcessor::INDEXER_ID;
    }

    /** @test */
    public function shouldPublishCategoryEditedUsingMagentoApplicationToStreamx() {
        // given
        $categoryOldName = 'Watches';
        $categoryNewName = 'Name modified for testing, at ' . date("Y-m-d H:i:s");
        $categoryId = MagentoMySqlQueryExecutor::getCategoryId($categoryOldName);

        // when
        self::renameCategory($categoryId, $categoryNewName);

        // then
        $expectedKey = "category_$categoryId";
        try {
            $this->assertDataIsPublished($expectedKey, $categoryNewName);
        } finally {
            self::renameCategory($categoryId, $categoryOldName);
            $this->assertDataIsPublished($expectedKey, $categoryOldName);
        }
    }

    private function renameCategory(int $categoryId, string $newName) {
        $this->callRestApiEndpoint('category/rename', [
            'categoryId' => $categoryId,
            'newName' => $newName
        ]);
    }
}
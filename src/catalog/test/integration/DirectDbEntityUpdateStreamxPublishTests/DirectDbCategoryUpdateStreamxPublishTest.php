<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\Indexer\CategoryProcessor;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoMySqlQueryExecutor;
use function date;

/**
 * @inheritdoc
 */
class DirectDbCategoryUpdateStreamxPublishTest extends BaseDirectDbEntityUpdateStreamxPublishTest {

    protected function indexerName(): string {
        return CategoryProcessor::INDEXER_ID;
    }

    /** @test */
    public function shouldPublishCategoryEditedDirectlyInDatabaseToStreamx() {
        // given
        $categoryOldName = 'Watches';
        $categoryNewName = 'Name modified for testing, at ' . date("Y-m-d H:i:s");
        $categoryId = MagentoMySqlQueryExecutor::getCategoryId($categoryOldName);

        // when
        self::renameCategoryInDb($categoryId, $categoryNewName);
        $this->indexerOperations->reindex();

        // then
        try {
            $expectedKey = "category_$categoryId";
            $this->assertDataIsPublished($expectedKey, $categoryNewName);
        } finally {
            self::renameCategoryInDb($categoryId, $categoryOldName);
        }
    }

    private static function renameCategoryInDb(int $categoryId, string $newName) {
        $categoryNameAttributeId = MagentoMySqlQueryExecutor::getCategoryNameAttributeId();
        MagentoMySqlQueryExecutor::execute("
            UPDATE catalog_category_entity_varchar
               SET value = '$newName'
             WHERE attribute_id = $categoryNameAttributeId
               AND entity_id = $categoryId
        ");
    }
}
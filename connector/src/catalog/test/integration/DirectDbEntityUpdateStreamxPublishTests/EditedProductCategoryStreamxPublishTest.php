<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoMySqlQueryExecutor;

/**
 * @inheritdoc
 */
class EditedProductCategoryStreamxPublishTest extends BaseEditedEntityStreamxPublishTest {

    protected function indexerName(): string {
        // note 1: no mview based indexer is implemented directly for Product-Category mappings
        // note 2: it is currently implemented via product indexer's mview
        return ProductProcessor::INDEXER_ID;
    }

    /** @test */
    public function shouldPublishProductCategoryEditedDirectlyInDatabaseToStreamx() {
        // given
        $productId = 1;
        $newCategoryName = 'Jackets';
        $newCategoryId = MagentoMySqlQueryExecutor::getCategoryId($newCategoryName);

        // read ID of first category assigned to the product
        $oldCategoryId = MagentoMySqlQueryExecutor::selectFirstField(<<<EOD
            SELECT MIN(category_id)
              FROM catalog_category_product
             WHERE product_id = $productId
        EOD);

        $this->assertNotEquals($newCategoryId, $oldCategoryId);

        // when
        self::changeProductCategoryInDb($productId, $oldCategoryId, $newCategoryId);
        $this->indexerOperations->reindex();

        // then
        try {
            $expectedKey = "product_$productId";
            $this->assertDataIsPublished($expectedKey, $newCategoryName);
        } finally {
            self::changeProductCategoryInDb($productId, $newCategoryId, $oldCategoryId);
        }
    }

    private static function changeProductCategoryInDb(int $productId, string $oldCategoryId, string $newCategoryId) {
        MagentoMySqlQueryExecutor::execute(<<<EOD
            UPDATE catalog_category_product
               SET category_id = $newCategoryId
             WHERE category_id = $oldCategoryId
               AND product_id = $productId
        EOD);
    }
}
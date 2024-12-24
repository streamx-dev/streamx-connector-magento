<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoMySqlQueryExecutor;

/**
 * @inheritdoc
 */
class ProductCategoryUpdateTest extends BaseDirectDbEntityUpdateTest {

    protected function indexerName(): string {
        // note 1: no mview based indexer is implemented directly for Product-Category mappings
        // note 2: it is currently implemented via product indexer's mview
        return ProductProcessor::INDEXER_ID;
    }

    /** @test */
    public function shouldPublishProductCategoryEditedDirectlyInDatabaseToStreamx() {
        // given
        $productName = 'Joust Duffle Bag';
        $productId = MagentoMySqlQueryExecutor::getProductId($productName);

        $newCategoryName = 'Jackets';
        $newCategoryId = MagentoMySqlQueryExecutor::getCategoryId($newCategoryName);

        // read ID of first category assigned to the product
        $oldCategoryId = MagentoMySqlQueryExecutor::selectFirstField("
            SELECT MIN(category_id)
              FROM catalog_category_product
             WHERE product_id = $productId
        ");

        $this->assertNotEquals($newCategoryId, $oldCategoryId);

        // and
        $expectedKey = "product_$productId";
        self::removeFromStreamX($expectedKey);

        // when
        self::changeProductCategoryInDb($productId, $oldCategoryId, $newCategoryId);

        try {
            // and
            $this->reindexMview();

            // then
            $this->assertDataIsPublished($expectedKey, $newCategoryName);
        } finally {
            self::changeProductCategoryInDb($productId, $newCategoryId, $oldCategoryId);
        }
    }

    private static function changeProductCategoryInDb(int $productId, string $oldCategoryId, string $newCategoryId) {
        MagentoMySqlQueryExecutor::execute("
            UPDATE catalog_category_product
               SET category_id = $newCategoryId
             WHERE category_id = $oldCategoryId
               AND product_id = $productId
        ");
    }
}
<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoMySqlQueryExecutor;

/**
 * @inheritdoc
 */
class ProductCategoryUpdateTest extends BaseAppEntityUpdateTest {

    protected function indexerName(): string {
        return ProductProcessor::INDEXER_ID;
    }

    /** @test */
    public function shouldPublishProductCategoryEditedUsingMagentoApplicationToStreamx() {
        // given
        $productName = 'Joust Duffle Bag';
        $productId = MagentoMySqlQueryExecutor::getProductId($productName);

        $newCategoryName = 'Jackets';
        $newCategoryId = MagentoMySqlQueryExecutor::getCategoryId($newCategoryName);

        // read ID (and name) of first category assigned to the product
        $oldCategoryId = MagentoMySqlQueryExecutor::selectFirstField("
            SELECT MIN(category_id)
              FROM catalog_category_product
             WHERE product_id = $productId
        ");
        $oldCategoryName = MagentoMySqlQueryExecutor::selectFirstField("
            SELECT value
              FROM catalog_category_entity_varchar
             WHERE attribute_id = " . MagentoMySqlQueryExecutor::getCategoryNameAttributeId() . "
               AND entity_id = $oldCategoryId
        ");

        $this->assertNotEquals($newCategoryId, $oldCategoryId);

        // and
        $expectedKey = "product_$productId";
        self::removeFromStreamX($expectedKey);

        // when
        self::changeProductCategory($productId, $oldCategoryId, $newCategoryId);

        // then
        try {
            $this->assertDataIsPublished($expectedKey, $newCategoryName);
        } finally {
            self::changeProductCategory($productId, $newCategoryId, $oldCategoryId);
            $this->assertDataIsPublished($expectedKey, $oldCategoryName);
        }
    }

    private function changeProductCategory(int $productId, int $oldCategoryId, int $newCategoryId): void {
        $this->callMagentoEndpoint('product/category/change', [
            'productId' => $productId,
            'oldCategoryId' => $oldCategoryId,
            'newCategoryId' => $newCategoryId
        ]);
    }
}
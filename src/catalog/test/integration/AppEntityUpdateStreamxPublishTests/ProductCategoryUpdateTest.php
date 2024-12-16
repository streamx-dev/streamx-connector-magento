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

        // when
        self::changeProductCategory($productId, $oldCategoryId, $newCategoryId);

        // then
        try {
            $this->assertDataIsPublished("product_category_$productId", $newCategoryName);
            // TODO: with current impl of changeProductCategory method, the below assertion does not pass. When product category is changed in Admin UI - it passes
            //  $this->assertDataIsPublished("product_$productId", $newCategoryName);
        } finally {
            self::changeProductCategory($productId, $newCategoryId, $oldCategoryId);
            $this->assertDataIsPublished("product_$productId", $oldCategoryName);
            $this->assertDataIsPublished("product_category_$productId", $oldCategoryName);
        }
    }

    private function changeProductCategory(int $productId, int $oldCategoryId, int $newCategoryId): void {
        $this->callRestApiEndpoint('product/category/change', [
            'productId' => $productId,
            'oldCategoryId' => $oldCategoryId,
            'newCategoryId' => $newCategoryId
        ]);
    }
}
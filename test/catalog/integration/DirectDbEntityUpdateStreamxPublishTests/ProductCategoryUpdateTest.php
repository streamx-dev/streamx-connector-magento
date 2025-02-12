<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;

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
        $productId = $this->db->getProductId($productName);

        $newCategoryName = 'Jackets';
        $newCategoryId = $this->db->getCategoryId($newCategoryName);

        // read ID of first category assigned to the product
        $oldCategoryId = $this->db->selectFirstField("
            SELECT MIN(category_id)
              FROM catalog_category_product
             WHERE product_id = $productId
        ");

        $this->assertNotEquals($newCategoryId, $oldCategoryId);

        // and
        $expectedKey = "pim:$productId";
        self::removeFromStreamX($expectedKey);

        // when
        $this->changeProductCategoryInDb($productId, $oldCategoryId, $newCategoryId);

        try {
            // and
            $this->reindexMview();

            // then
            $this->assertExactDataIsPublished($expectedKey, 'bag-with-edited-category.json');
        } finally {
            $this->changeProductCategoryInDb($productId, $newCategoryId, $oldCategoryId);
        }
    }

    private function changeProductCategoryInDb(int $productId, string $oldCategoryId, string $newCategoryId) {
        $this->db->execute("
            UPDATE catalog_category_product
               SET category_id = $newCategoryId
             WHERE category_id = $oldCategoryId
               AND product_id = $productId
        ");
    }
}
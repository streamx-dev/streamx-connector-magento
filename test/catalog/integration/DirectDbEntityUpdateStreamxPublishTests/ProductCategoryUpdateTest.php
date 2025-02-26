<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

/**
 * @inheritdoc
 * @UsesProductIndexer
 * note 1: no mview based indexer is implemented directly for Product-Category mappings
 * note 2: it is currently implemented via product indexer's mview
 */
class ProductCategoryUpdateTest extends BaseDirectDbEntityUpdateTest {

    /** @test */
    public function shouldPublishProductCategoryEditedDirectlyInDatabaseToStreamx() {
        // given
        $productName = 'Joust Duffle Bag';
        $productId = self::$db->getProductId($productName);

        $newCategoryName = 'Jackets';
        $newCategoryId = self::$db->getCategoryId($newCategoryName);

        // read ID of first category assigned to the product
        $oldCategoryId = self::$db->selectSingleValue("
            SELECT MIN(category_id)
              FROM catalog_category_product
             WHERE product_id = $productId
        ");

        $this->assertNotEquals($newCategoryId, $oldCategoryId);

        // and
        $expectedKey = "default_product:$productId";
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
        self::$db->execute("
            UPDATE catalog_category_product
               SET category_id = $newCategoryId
             WHERE category_id = $oldCategoryId
               AND product_id = $productId
        ");
    }
}
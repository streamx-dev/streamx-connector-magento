<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\utils\CodeCoverageReportGenerator;

/**
 * @inheritdoc
 * @UsesProductIndexer
 */
class ProductCategoryUpdateTest extends BaseAppEntityUpdateTest {

    /** @test */
    public function shouldPublishProductCategoryEditedUsingMagentoApplicationToStreamx() {
        // given
        $productName = 'Joust Duffle Bag';
        $productId = self::$db->getProductId($productName);

        $newCategoryName = 'Jackets';
        $newCategoryId = self::$db->getCategoryId($newCategoryName);

        // read ID (and name) of first category assigned to the product
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
        self::changeProductCategory($productId, $oldCategoryId, $newCategoryId);

        // then
        try {
            $this->assertExactDataIsPublished($expectedKey, 'bag-with-edited-category.json');
        } finally {
            self::changeProductCategory($productId, $newCategoryId, $oldCategoryId);
            $this->assertExactDataIsPublished($expectedKey, 'original-bag-product.json');
        }
    }

    private function changeProductCategory(int $productId, int $oldCategoryId, int $newCategoryId): void {
        $coverage = self::callMagentoPutEndpoint('product/category/change', [
            'productId' => $productId,
            'oldCategoryId' => $oldCategoryId,
            'newCategoryId' => $newCategoryId
        ]);

        if (getenv('GENERATE_CODE_COVERAGE_REPORT') === 'true') {
            CodeCoverageReportGenerator::generateCodeCoverageReport($coverage, $this);
        }
    }
}
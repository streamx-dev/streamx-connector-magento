<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCatalog\test\integration\utils\CodeCoverageReportGenerator;

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
        $productId = $this->db->getProductId($productName);

        $newCategoryName = 'Jackets';
        $newCategoryId = $this->db->getCategoryId($newCategoryName);

        // read ID (and name) of first category assigned to the product
        $oldCategoryId = $this->db->selectFirstField("
            SELECT MIN(category_id)
              FROM catalog_category_product
             WHERE product_id = $productId
        ");
        $oldCategoryName = $this->db->selectFirstField("
            SELECT value
              FROM catalog_category_entity_varchar
             WHERE attribute_id = " . $this->db->getCategoryNameAttributeId() . "
               AND entity_id = $oldCategoryId
        ");

        $this->assertNotEquals($newCategoryId, $oldCategoryId);

        // and
        $expectedKey = "pim:$productId";
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
        $coverage = $this->callMagentoPutEndpoint('product/category/change', [
            'productId' => $productId,
            'oldCategoryId' => $oldCategoryId,
            'newCategoryId' => $newCategoryId
        ]);

        if (getenv('GENERATE_CODE_COVERAGE_REPORT') === 'true') {
            CodeCoverageReportGenerator::generateCodeCoverageReport($coverage, $this);
        }
    }
}
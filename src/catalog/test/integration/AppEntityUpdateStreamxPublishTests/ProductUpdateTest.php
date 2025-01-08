<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCatalog\test\integration\utils\CodeCoverageReportGenerator;

/**
 * @inheritdoc
 */
class ProductUpdateTest extends BaseAppEntityUpdateTest {

    protected function indexerName(): string {
        return ProductProcessor::INDEXER_ID;
    }

    /** @test */
    public function shouldPublishProductEditedUsingMagentoApplicationToStreamx() {
        // given
        $productOldName = 'Joust Duffle Bag';
        $productNewName = 'Name modified for testing';
        $productId = $this->db->getProductId($productOldName);

        // and
        $expectedKey = "product_$productId";
        self::removeFromStreamX($expectedKey);

        // when
        self::renameProduct($productId, $productNewName);

        // then
        try {
            $this->assertExactDataIsPublished($expectedKey, 'edited-bag-product.json');
        } finally {
            self::renameProduct($productId, $productOldName);
            $this->assertExactDataIsPublished($expectedKey, 'original-bag-product.json');
        }
    }

    private function renameProduct(int $productId, string $newName): void {
        $coverage = $this->callMagentoPutEndpoint('product/rename', [
            'productId' => $productId,
            'newName' => $newName
        ]);

        if (getenv('GENERATE_CODE_COVERAGE_REPORT') === 'true') {
            CodeCoverageReportGenerator::generateCodeCoverageReport($coverage, $this);
        }
    }
}
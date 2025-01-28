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
    public function shouldPublishSimpleProductEditedUsingMagentoApplicationToStreamx() {
        $this->shouldPublishProductEditedUsingMagentoApplicationToStreamx('Joust Duffle Bag', 'bag');
    }

    /** @test */
    public function shouldPublishConfigurableProductEditedUsingMagentoApplicationToStreamx() {
        $this->setConfigurationValue($this->ADD_SWATCHES_PATH, '1');
        try {
            // TODO: currently swatches info is not added to the published product json even when the above setting is turned on
            $this->shouldPublishProductEditedUsingMagentoApplicationToStreamx('Chaz Kangeroo Hoodie', 'hoodie');
        } finally {
            $this->restoreConfigurationValue($this->ADD_SWATCHES_PATH);
        }
    }

    private function shouldPublishProductEditedUsingMagentoApplicationToStreamx(string $productName, string $productType): void {
        // given
        $productNewName = "Name modified for testing, was $productName";
        $productId = $this->db->getProductId($productName);

        // and
        $expectedKey = "pim:$productId";
        self::removeFromStreamX($expectedKey);

        // when
        self::renameProduct($productId, $productNewName);

        // then
        try {
            $this->assertExactDataIsPublished($expectedKey, "edited-$productType-product.json");
        } finally {
            self::renameProduct($productId, $productName);
            $this->assertExactDataIsPublished($expectedKey, "original-$productType-product.json");
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
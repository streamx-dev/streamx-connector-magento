<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\utils\CodeCoverageReportGenerator;

/**
 * @inheritdoc
 * @UsesProductIndexer
 */
class ProductUpdateTest extends BaseAppEntityUpdateTest {

    /** @test */
    public function shouldPublishSimpleProductEditedUsingMagentoApplicationToStreamx() {
        $this->shouldPublishProductEditedUsingMagentoApplicationToStreamx('Joust Duffle Bag', 'bag');
    }

    /** @test */
    public function shouldPublishSimpleProductEditedUsingMagentoApplicationToStreamxWithoutAttributes() {
        $this->setConfigurationValue($this->PRODUCT_ATTRIBUTES_PATH, 'cost'); // index only an attr that bags don't have (so no attr expected in publish payload)
        try {
            $this->shouldPublishProductEditedUsingMagentoApplicationToStreamx('Joust Duffle Bag', 'bag-no-attributes');
        } finally {
            $this->restoreConfigurationValue($this->PRODUCT_ATTRIBUTES_PATH);
        }
    }

    /** @test */
    public function shouldPublishBundleProductEditedUsingMagentoApplicationToStreamx() {
        $this->shouldPublishProductEditedUsingMagentoApplicationToStreamx('Sprite Yoga Companion Kit', 'bundle');
    }

    /** @test */
    public function shouldPublishGroupedProductEditedUsingMagentoApplicationToStreamx() {
        // TODO: the produced json doesn't contain information about the components that make up the grouped product
        $this->shouldPublishProductEditedUsingMagentoApplicationToStreamx('Set of Sprite Yoga Straps', 'grouped');
    }

    private function shouldPublishProductEditedUsingMagentoApplicationToStreamx(string $productName, string $productNameInValidationFileName): void {
        // given
        $productNewName = "Name modified for testing, was $productName";
        $productId = self::$db->getProductId($productName);

        // and
        $expectedKey = "pim:$productId";
        self::removeFromStreamX($expectedKey);

        // when
        self::renameProduct($productId, $productNewName);

        // then
        try {
            $this->assertExactDataIsPublished($expectedKey, "edited-$productNameInValidationFileName-product.json");
        } finally {
            self::renameProduct($productId, $productName);
            $this->assertExactDataIsPublished($expectedKey, "original-$productNameInValidationFileName-product.json");
        }
    }

    private function renameProduct(int $productId, string $newName): void {
        $coverage = self::callMagentoPutEndpoint('product/rename', [
            'productId' => $productId,
            'newName' => $newName
        ]);

        if (getenv('GENERATE_CODE_COVERAGE_REPORT') === 'true') {
            CodeCoverageReportGenerator::generateCodeCoverageReport($coverage, $this);
        }
    }
}
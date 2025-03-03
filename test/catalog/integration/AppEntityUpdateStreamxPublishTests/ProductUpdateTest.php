<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\utils\CodeCoverageReportGenerator;
use StreamX\ConnectorCatalog\test\integration\utils\EntityIds;

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
        $this->setIndexedProductAttributes('cost'); // index only an attr that bags don't have (so no attr expected in publish payload)
        try {
            $this->shouldPublishProductEditedUsingMagentoApplicationToStreamx('Joust Duffle Bag', 'bag-no-attributes');
        } finally {
            $this->restoreDefaultIndexedProductAttributes();
        }
    }

    /** @test */
    public function shouldPublishBundleProductEditedUsingMagentoApplicationToStreamx() {
        $regexReplacements = self::$db->isEnterpriseMagento() ? [ // in enterprise magento DB, ID of the bundle product is 46, not 45 as in community version
            '"id": 45,' => '"id": 46,',
            '-45"' => '-46"'
        ] : [];
        $this->shouldPublishProductEditedUsingMagentoApplicationToStreamx('Sprite Yoga Companion Kit', 'bundle', $regexReplacements);
    }

    /** @test */
    public function shouldPublishGroupedProductEditedUsingMagentoApplicationToStreamx() {
        $regexReplacements = self::$db->isEnterpriseMagento() ? [ // in enterprise magento DB, ID of the grouped product is 45, not 46 as in community version
            '"id": 46,' => '"id": 45,',
            '-46"' => '-45"'
        ] : [];
        // TODO: the produced json doesn't contain information about the components that make up the grouped product
        $this->shouldPublishProductEditedUsingMagentoApplicationToStreamx('Set of Sprite Yoga Straps', 'grouped', $regexReplacements);
    }

    private function shouldPublishProductEditedUsingMagentoApplicationToStreamx(string $productName, string $productNameInValidationFileName, array $regexReplacements = []): void {
        // given
        $productNewName = "Name modified for testing, was $productName";
        $productId = self::$db->getProductId($productName);

        // and
        $expectedKey = self::productKey($productId);
        self::removeFromStreamX($expectedKey);

        // when
        self::renameProduct($productId, $productNewName);

        // then
        try {
            $this->assertExactDataIsPublished($expectedKey, "edited-$productNameInValidationFileName-product.json", $regexReplacements);
        } finally {
            self::renameProduct($productId, $productName);
            $this->assertExactDataIsPublished($expectedKey, "original-$productNameInValidationFileName-product.json", $regexReplacements);
        }
    }

    private function renameProduct(EntityIds $productId, string $newName): void {
        $coverage = self::callMagentoPutEndpoint('product/rename', [
            'productId' => $productId->getEntityId(),
            'newName' => $newName
        ]);

        if (getenv('GENERATE_CODE_COVERAGE_REPORT') === 'true') {
            CodeCoverageReportGenerator::generateCodeCoverageReport($coverage, $this);
        }
    }
}
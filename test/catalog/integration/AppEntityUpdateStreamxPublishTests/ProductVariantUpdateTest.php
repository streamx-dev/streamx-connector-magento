<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\SlugGenerator;
use StreamX\ConnectorCatalog\test\integration\utils\CodeCoverageReportGenerator;

/**
 * @inheritdoc
 * @UsesProductIndexer
 */
class ProductVariantUpdateTest extends BaseAppEntityUpdateTest {

    /** @test */
    public function shouldPublishParentProductAndAllVariants_WhenParentIsEditedUsingMagentoApplication() {
        // given
        $parentProductName = 'Chaz Kangeroo Hoodie';
        $parentProductId = self::$db->getProductId($parentProductName);

        $childProducts = self::$db->getProductIdsAndNamesMap("$parentProductName-");
        $this->assertCount(15, $childProducts);

        // and
        $expectedParentProductKey = "pim:$parentProductId";
        $expectedChildProductsKeys = array_map(function ($childProductId) {
            return "pim:$childProductId";
        }, array_keys($childProducts));

        self::removeFromStreamX($expectedParentProductKey, ...$expectedChildProductsKeys);

        // when
        self::renameProduct($parentProductId, "Name modified for testing, was $parentProductName");

        // then
        try {
            $this->assertExactDataIsPublished($expectedParentProductKey, 'edited-hoodie-product.json');
            foreach ($childProducts as $childProductId => $childProductName) {
                $publishedChildProduct = $this->downloadContentAtKey("pim:$childProductId");
                $this->assertStringContainsString('"id":' . $childProductId, $publishedChildProduct);
                $this->assertStringContainsString('"name":"' . $childProductName . '"', $publishedChildProduct);
            }
        } finally {
            self::renameProduct($parentProductId, $parentProductName);
            $this->assertExactDataIsPublished($expectedParentProductKey, 'original-hoodie-product.json');
        }
    }

    /** @test */
    public function shouldPublishVariantAndParentProduct_WhenVariantIsEditedUsingMagentoApplication() {
        // given
        $childProductName = 'Chaz Kangeroo Hoodie-XL-Orange';
        $childProductId = self::$db->getProductId($childProductName);

        $parentProductName = 'Chaz Kangeroo Hoodie';
        $parentProductId = self::$db->getProductId($parentProductName);

        // and
        $expectedChildProductKey = "pim:$childProductId";
        $expectedParentProductKey = "pim:$parentProductId";
        $unexpectedChildProductKey = 'pim:' . self::$db->getProductId('Chaz Kangeroo Hoodie-L-Orange'); // a different child of the same parent product

        self::removeFromStreamX($expectedChildProductKey, $expectedParentProductKey, $unexpectedChildProductKey);

        // when
        $childProductModifiedName = "Name modified for testing, was $childProductName";
        self::renameProduct($childProductId, $childProductModifiedName);

        // then: expecting both products to be published (with modified name of the child product in both payloads). Other child should not be published
        try {
            $this->assertExactDataIsPublished($expectedChildProductKey, 'original-hoodie-xl-orange-product.json', [
                '"' . $childProductName => '"' . $childProductModifiedName,
                '"' . SlugGenerator::slugify($childProductName) => '"' . SlugGenerator::slugify($childProductModifiedName)
            ]);
            $this->assertExactDataIsPublished($expectedParentProductKey, 'original-hoodie-product.json', [
                '"' . $childProductName => '"' . $childProductModifiedName,
                '"' . SlugGenerator::slugify($childProductName) => '"' . SlugGenerator::slugify($childProductModifiedName)
            ]);
            $this->assertDataIsNotPublished($unexpectedChildProductKey);
        } finally {
            // when: restore product name
            self::renameProduct($childProductId, $childProductName);

            // then: expecting both products to be published (with original name of the child product in both payloads)
            $this->assertExactDataIsPublished($expectedChildProductKey, 'original-hoodie-xl-orange-product.json');
            $this->assertExactDataIsPublished($expectedParentProductKey, 'original-hoodie-product.json');
            $this->assertDataIsNotPublished($unexpectedChildProductKey);
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
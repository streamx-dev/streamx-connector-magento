<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use Magento\Catalog\Model\Product\Visibility;
use StreamX\ConnectorCatalog\Model\SlugGenerator;
use StreamX\ConnectorCatalog\test\integration\utils\CodeCoverageReportGenerator;

/**
 * @inheritdoc
 * @UsesProductIndexer
 */
class ProductVariantUpdateTest extends BaseAppEntityUpdateTest {

    private const PARENT_PRODUCT_NAME = 'Chaz Kangeroo Hoodie';
    private const CHILD_PRODUCT_NAME = 'Chaz Kangeroo Hoodie-XL-Orange';

    /** @test */
    public function shouldPublishParentProductAndVisibleVariants_WhenParentIsEditedUsingMagentoApplication() {
        // given
        $parentProductId = self::$db->getProductId(self::PARENT_PRODUCT_NAME);

        $childProducts = self::$db->getProductIdsAndNamesMap(self::PARENT_PRODUCT_NAME . '-');
        $this->assertCount(15, $childProducts);

        // and: make some of the child products visible
        $visibilityAttributeId = self::$db->getProductAttributeId('visibility');
        foreach (array_keys($childProducts) as $childId) {
            if ($childId %2 == 0) {
                self::$db->insertIntProductAttribute($childId, $visibilityAttributeId, self::STORE_1_ID, Visibility::VISIBILITY_IN_SEARCH);
                $visibleChildProducts[$childId] = $childProducts[$childId];
            } else {
                $invisibleChildProducts[$childId] = $childProducts[$childId];
            }
        }

        // and
        $expectedParentProductKey = "pim:$parentProductId";
        $expectedChildProductsKeys = array_map(function ($childProductId) {
            return "pim:$childProductId";
        }, array_keys($childProducts));

        self::removeFromStreamX($expectedParentProductKey, ...$expectedChildProductsKeys);

        // when
        self::renameProduct($parentProductId, "Name modified for testing, was " . self::PARENT_PRODUCT_NAME);

        // then
        try {
            $this->assertExactDataIsPublished($expectedParentProductKey, 'edited-hoodie-product.json');
            foreach ($visibleChildProducts as $childProductId => $childProductName) {
                $publishedChildProduct = $this->downloadContentAtKey("pim:$childProductId");
                $this->assertStringContainsString('"id":' . $childProductId, $publishedChildProduct);
                $this->assertStringContainsString('"name":"' . $childProductName . '"', $publishedChildProduct);
            }
            foreach ($invisibleChildProducts as $childProductId => $childProductName) {
                $this->assertDataIsNotPublished("pim:$childProductId");
            }
        } finally {
            self::renameProduct($parentProductId, self::PARENT_PRODUCT_NAME);
            try {
                $this->assertExactDataIsPublished($expectedParentProductKey, 'original-hoodie-product.json');
            } finally {
                // restore default visibility of child products
                foreach (array_keys($visibleChildProducts) as $childProductId) {
                    self::$db->deleteIntProductAttribute($childProductId, $visibilityAttributeId, self::STORE_1_ID);
                }
            }
        }
    }

    /** @test */
    public function shouldPublishVisibleVariantAndParentProduct_WhenVariantIsEditedUsingMagentoApplication() {
        $childProductId = self::$db->getProductId(self::CHILD_PRODUCT_NAME);
        $visibilityAttributeId = self::$db->getProductAttributeId('visibility');
        try {
            // make the variant visible at store level, so it can be published
            self::$db->insertIntProductAttribute($childProductId, $visibilityAttributeId, self::STORE_1_ID, Visibility::VISIBILITY_IN_CATALOG);
            $this->testPublishingWhenVariantIsEdited(true);
        } finally {
            // restore no visibility for variant
            self::$db->deleteIntProductAttribute($childProductId, $visibilityAttributeId, self::STORE_1_ID);
        }
    }

    /** @test */
    public function shouldNotPublishInvisibleVariant_ButPublishParentProduct_WhenVariantIsEditedUsingMagentoApplication() {
        $this->testPublishingWhenVariantIsEdited(false);
    }

    private function testPublishingWhenVariantIsEdited(bool $expectingVariantToBePublished) {
        // given
        $childProductId = self::$db->getProductId(self::CHILD_PRODUCT_NAME);
        $parentProductId = self::$db->getProductId(self::PARENT_PRODUCT_NAME);

        // and
        $expectedChildProductKey = "pim:$childProductId";
        $expectedParentProductKey = "pim:$parentProductId";
        $unexpectedChildProductKey = 'pim:' . self::$db->getProductId('Chaz Kangeroo Hoodie-L-Orange'); // a different child of the same parent product

        self::removeFromStreamX($expectedChildProductKey, $expectedParentProductKey, $unexpectedChildProductKey);

        // when
        $childProductModifiedName = "Name modified for testing, was " . self::CHILD_PRODUCT_NAME;
        self::renameProduct($childProductId, $childProductModifiedName);

        // then: expecting both products to be published (with modified name of the child product in both payloads). Other child should not be published
        // note: $expectingVariantToBePublished is passed as a flag, because publishing a variant depends on its visibility setting
        try {
            if ($expectingVariantToBePublished) {
                $this->assertExactDataIsPublished($expectedChildProductKey, 'original-hoodie-xl-orange-product.json', [
                    '"' . self::CHILD_PRODUCT_NAME => '"' . $childProductModifiedName,
                    '"' . SlugGenerator::slugify(self::CHILD_PRODUCT_NAME) => '"' . SlugGenerator::slugify($childProductModifiedName)
                ]);
            } else {
                $this->assertDataIsNotPublished($expectedChildProductKey);
            }
            $this->assertExactDataIsPublished($expectedParentProductKey, 'original-hoodie-product.json', [
                '"' . self::CHILD_PRODUCT_NAME => '"' . $childProductModifiedName,
                '"' . SlugGenerator::slugify(self::CHILD_PRODUCT_NAME) => '"' . SlugGenerator::slugify($childProductModifiedName)
            ]);
            $this->assertDataIsNotPublished($unexpectedChildProductKey);
        } finally {
            // when: restore product name
            self::renameProduct($childProductId, self::CHILD_PRODUCT_NAME);

            // then: expecting both products to be published (with original name of the child product in both payloads)
            if ($expectingVariantToBePublished) {
                $this->assertExactDataIsPublished($expectedChildProductKey, 'original-hoodie-xl-orange-product.json');
            } else {
                $this->assertDataIsNotPublished($expectedChildProductKey);
            }
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
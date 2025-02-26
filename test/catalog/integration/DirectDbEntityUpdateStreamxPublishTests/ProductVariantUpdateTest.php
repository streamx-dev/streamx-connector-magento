<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\SlugGenerator;

/**
 * @inheritdoc
 * @UsesProductIndexer
 */
class ProductVariantUpdateTest extends BaseDirectDbEntityUpdateTest {

    private const PARENT_PRODUCT_NAME = 'Chaz Kangeroo Hoodie';
    private const CHILD_PRODUCT_NAME = 'Chaz Kangeroo Hoodie-XL-Orange';

    /** @test */
    public function shouldPublishParentProductAndVisibleVariants_WhenParentIsEditedDirectlyInDatabase() {
        // given
        $parentProductId = self::$db->getProductId(self::PARENT_PRODUCT_NAME);

        $childProducts = self::$db->getProductIdsAndNamesMap(self::PARENT_PRODUCT_NAME . '-');
        $this->assertCount(15, $childProducts);

        // and: make some of the child products visible
        foreach (array_keys($childProducts) as $childId) {
            if ($childId %2 == 1) {
                $visibleChildProducts[$childId] = $childProducts[$childId];
            } else {
                $invisibleChildProducts[$childId] = $childProducts[$childId];
            }
        }
        self::$db->setProductsVisibleInStore(self::STORE_1_ID, ...array_keys($visibleChildProducts));

        // and
        $expectedParentProductKey = "pim:$parentProductId";
        $expectedChildProductsKeys = array_map(function ($childProductId) {
            return "pim:$childProductId";
        }, array_keys($childProducts));

        self::removeFromStreamX($expectedParentProductKey, ...$expectedChildProductsKeys);

        // when
        self::$db->renameProduct($parentProductId, "Name modified for testing, was " . self::PARENT_PRODUCT_NAME);

        try {
            // and
            $this->reindexMview();

            // then
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
            self::$db->renameProduct($parentProductId, self::PARENT_PRODUCT_NAME);
            // restore default visibility of child products
            self::$db->unsetProductsVisibleInStore(self::STORE_1_ID, ...array_keys($visibleChildProducts));
        }
    }

    /** @test */
    public function shouldPublishVisibleVariantAndParentProduct_WhenVariantIsEditedUsingDirectlyInDatabase() {
        $childProductId = self::$db->getProductId(self::CHILD_PRODUCT_NAME);
        try {
            // make the variant visible at store level, so it can be published
            self::$db->setProductsVisibleInStore(self::STORE_1_ID, $childProductId);
            $this->testPublishingWhenVariantIsEdited(true);
        } finally {
            // restore no visibility for variant
            self::$db->unsetProductsVisibleInStore(self::STORE_1_ID, $childProductId);
        }
    }

    /** @test */
    public function shouldNotPublishInvisibleVariant_ButPublishParentProduct_WhenVariantIsEditedUsingDirectlyInDatabase() {
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
        self::$db->renameProduct($childProductId, $childProductModifiedName);

        try {
            // and
            $this->reindexMview();

            // then: expecting both products to be published (with modified name of the child product in both payloads). Other child should not be published
            // note: $expectingVariantToBePublished is passed as a flag, because publishing a variant depends on its visibility setting
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
            self::$db->renameProduct($childProductId, self::CHILD_PRODUCT_NAME);
        }
    }
}
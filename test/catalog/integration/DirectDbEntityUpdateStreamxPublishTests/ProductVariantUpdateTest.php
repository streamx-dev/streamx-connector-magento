<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCatalog\Model\SlugGenerator;

/**
 * @inheritdoc
 */
class ProductVariantUpdateTest extends BaseDirectDbEntityUpdateTest {

    const INDEXER_IDS = [ProductProcessor::INDEXER_ID];

    private const PARENT_PRODUCT_NAME = 'Chaz Kangeroo Hoodie';

    /** @test */
    public function shouldPublishParentProductAndVisibleVariants_WhenParentIsEditedDirectlyInDatabase() {
        // given
        $parentProductId = self::$db->getProductId(self::PARENT_PRODUCT_NAME);

        // take first 4 variant products, and group them to visible and invisible products
        $visibleChildProduct1 = self::$db->getProductId('Chaz Kangeroo Hoodie-XS-Black');
        $visibleChildProduct2 = self::$db->getProductId('Chaz Kangeroo Hoodie-XS-Gray');
        $invisibleChildProduct1 = self::$db->getProductId('Chaz Kangeroo Hoodie-XS-Orange');
        $invisibleChildProduct2 = self::$db->getProductId('Chaz Kangeroo Hoodie-S-Black');
        self::$db->setProductsVisibleInStore(self::$store1Id, $visibleChildProduct1, $visibleChildProduct2); // by default variants are not visible in store

        // remove from StreamX, if published before
        $expectedParentProductKey = self::productKey($parentProductId);
        $visibleChildProduct1Key = self::productKey($visibleChildProduct1);
        $visibleChildProduct2Key = self::productKey($visibleChildProduct2);
        $invisibleChildProduct1Key = self::productKey($invisibleChildProduct1);
        $invisibleChildProduct2Key = self::productKey($invisibleChildProduct2);

        self::removeFromStreamX($expectedParentProductKey,
            $visibleChildProduct1Key, $visibleChildProduct2Key,
            $invisibleChildProduct1Key, $invisibleChildProduct2Key
        );

        // when
        self::$db->renameProduct($parentProductId, "Name modified for testing, was " . self::PARENT_PRODUCT_NAME);

        try {
            // and
            $this->reindexMview();

            // then
            $this->assertExactDataIsPublished($expectedParentProductKey, 'edited-hoodie-product.json');
            $this->assertExactDataIsPublished($visibleChildProduct1Key, 'original-hoodie-xs-black-product.json');
            $this->assertExactDataIsPublished($visibleChildProduct2Key, 'original-hoodie-xs-gray-product.json');
            $this->assertDataIsNotPublished($invisibleChildProduct1Key);
            $this->assertDataIsNotPublished($invisibleChildProduct2Key);
        } finally {
            self::$db->renameProduct($parentProductId, self::PARENT_PRODUCT_NAME);
            // restore default visibility of child products
            self::$db->unsetProductsVisibleInStore(self::$store1Id, $visibleChildProduct1, $visibleChildProduct2);
        }
    }

    /** @test */
    public function shouldPublishVisibleVariantAndParentProduct_WhenVariantIsEditedDirectlyInDatabase() {
        $childProductId = self::$db->getProductId('Chaz Kangeroo Hoodie-XL-Orange');
        try {
            // make the variant visible at store level, so it can be published
            self::$db->setProductsVisibleInStore(self::$store1Id, $childProductId);
            $this->testPublishingWhenVariantIsEdited(true);
        } finally {
            // restore no visibility for variant
            self::$db->unsetProductsVisibleInStore(self::$store1Id, $childProductId);
        }
    }

    /** @test */
    public function shouldNotPublishInvisibleVariant_ButPublishParentProduct_WhenVariantIsEditedDirectlyInDatabase() {
        $this->testPublishingWhenVariantIsEdited(false);
    }

    private function testPublishingWhenVariantIsEdited(bool $expectingVariantToBePublished) {
        // given
        $childProductName = 'Chaz Kangeroo Hoodie-XL-Orange';
        $differentChildProductName = 'Chaz Kangeroo Hoodie-L-Orange';

        $childProductId = self::$db->getProductId($childProductName);
        $differentChildProductId = self::$db->getProductId($differentChildProductName);
        $parentProductId = self::$db->getProductId(self::PARENT_PRODUCT_NAME);

        // and
        $expectedChildProductKey = self::productKey($childProductId);
        $expectedParentProductKey = self::productKey($parentProductId);
        $unexpectedChildProductKey = self::productKey($differentChildProductId);

        self::removeFromStreamX($expectedChildProductKey, $expectedParentProductKey, $unexpectedChildProductKey);

        // when
        $childProductModifiedName = "Name modified for testing, was $childProductName";
        self::$db->renameProduct($childProductId, $childProductModifiedName);

        try {
            // and
            $this->reindexMview();

            // then: expecting both products to be published (with modified name of the child product in both payloads). Other child should not be published
            // note: $expectingVariantToBePublished is passed as a flag, because publishing a variant depends on its visibility setting
            if ($expectingVariantToBePublished) {
                $this->assertExactDataIsPublished($expectedChildProductKey, 'original-hoodie-xl-orange-product.json', [
                    '"' . $childProductModifiedName => '"' . $childProductName,
                    '"' . SlugGenerator::slugify($childProductModifiedName) => '"' . SlugGenerator::slugify($childProductName)
                ]);
            } else {
                $this->assertDataIsNotPublished($expectedChildProductKey);
            }
            $this->assertExactDataIsPublished($expectedParentProductKey, 'original-hoodie-product.json', [
                '"' . $childProductModifiedName => '"' . $childProductName,
                '"' . SlugGenerator::slugify($childProductModifiedName) => '"' . SlugGenerator::slugify($childProductName)
            ]);
            $this->assertDataIsNotPublished($unexpectedChildProductKey);
        } finally {
            self::$db->renameProduct($childProductId, $childProductName);
        }
    }
}
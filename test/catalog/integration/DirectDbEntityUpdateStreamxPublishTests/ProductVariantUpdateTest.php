<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\SlugGenerator;

/**
 * @inheritdoc
 * @UsesProductIndexer
 */
class ProductVariantUpdateTest extends BaseDirectDbEntityUpdateTest {

    /** @test */
    public function shouldPublishParentProductAndAllVariants_WhenParentIsEditedDirectlyInDatabase() {
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
        self::$db->renameProduct($parentProductId, "Name modified for testing, was $parentProductName");

        try {
            // and
            $this->reindexMview();

            // then
            $this->assertExactDataIsPublished($expectedParentProductKey, 'edited-hoodie-product.json');
            foreach ($childProducts as $childProductId => $childProductName) {
                $publishedChildProduct = $this->downloadContentAtKey("pim:$childProductId");
                $this->assertStringContainsString('"id":' . $childProductId, $publishedChildProduct);
                $this->assertStringContainsString('"name":"' . $childProductName . '"', $publishedChildProduct);
            }
        } finally {
            self::$db->renameProduct($parentProductId, $parentProductName);
        }
    }

    /** @test */
    public function shouldPublishVariantAndParentProduct_WhenVariantIsEditedUsingDirectlyInDatabase() {
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
        self::$db->renameProduct($childProductId, $childProductModifiedName);

        try {
            // and
            $this->reindexMview();

            // then: expecting both products to be published (with modified name of the child product in both payloads). Other child should not be published
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
            self::$db->renameProduct($childProductId, $childProductName);
        }
    }
}
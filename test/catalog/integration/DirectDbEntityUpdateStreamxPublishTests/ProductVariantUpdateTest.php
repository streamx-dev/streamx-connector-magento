<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCatalog\Model\SlugGenerator;

/**
 * @inheritdoc
 */
class ProductVariantUpdateTest extends BaseDirectDbEntityUpdateTest {

    protected function indexerName(): string {
        return ProductProcessor::INDEXER_ID;
    }

    /** @test */
    public function shouldPublishParentProductAndAllVariants_WhenParentIsEditedDirectlyInDatabase() {
        // given
        $parentProductName = 'Chaz Kangeroo Hoodie';
        $parentProductId = $this->db->getProductId($parentProductName);

        $childProducts = $this->db->getProductIdsAndNamesMap("$parentProductName-");
        $this->assertCount(15, $childProducts);

        // and
        $expectedParentProductKey = "pim:$parentProductId";
        $expectedChildProductsKeys = array_map(function ($childProductId) {
            return "pim:$childProductId";
        }, array_keys($childProducts));

        self::removeFromStreamX($expectedParentProductKey, ...$expectedChildProductsKeys);

        // when
        $this->db->renameProduct($parentProductId, "Name modified for testing, was $parentProductName");

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
            $this->db->renameProduct($parentProductId, $parentProductName);
        }
    }

    /** @test */
    public function shouldPublishVariantAndParentProduct_WhenVariantIsEditedUsingDirectlyInDatabase() {
        // given
        $childProductName = 'Chaz Kangeroo Hoodie-XL-Orange';
        $childProductId = $this->db->getProductId($childProductName);

        $parentProductName = 'Chaz Kangeroo Hoodie';
        $parentProductId = $this->db->getProductId($parentProductName);

        // and
        $expectedChildProductKey = "pim:$childProductId";
        $expectedParentProductKey = "pim:$parentProductId";
        $unexpectedChildProductKey = 'pim:' . $this->db->getProductId('Chaz Kangeroo Hoodie-L-Orange'); // a different child of the same parent product

        self::removeFromStreamX($expectedChildProductKey, $expectedParentProductKey, $unexpectedChildProductKey);

        // when
        $childProductModifiedName = "Name modified for testing, was $childProductName";
        $this->db->renameProduct($childProductId, $childProductModifiedName);

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
            $this->db->renameProduct($childProductId, $childProductName);
        }
    }
}
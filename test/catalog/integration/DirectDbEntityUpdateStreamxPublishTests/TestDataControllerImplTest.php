<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

/**
 * @inheritdoc
 * @UsesProductIndexer
 */
class TestDataControllerImplTest extends BaseDirectDbEntityUpdateTest {

    /** @test */
    public function magentoShouldContainTestDataFromImportedCsvFile() {
        // given
        $product1 = self::$db->getProductId("Rivet Bristol Natural Edge Black Metal Side Table, Walnut");
        $product2 = self::$db->getProductId("LeatherSoft Kids/Youth Recliner with Armrest Storage, 5+ Age Group, Light Blue");
        $product3 = self::$db->getProductId("Rivet Eva Mid-Century Modern Tufted Velvet Down-Filled Sectional Sofa Couch, 87\"W, Navy");

        $expectedKeyForProduct1 = self::productKey($product1);
        $expectedKeyForProduct2 = self::productKey($product2);
        $expectedKeyForProduct3 = self::productKey($product3);

        $this->removeFromStreamX($expectedKeyForProduct1, $expectedKeyForProduct2, $expectedKeyForProduct3);

        // when
        self::$db->productDummyUpdate($product1, $product2, $product3);
        self::reindexMview();

        // then
        try {
            $this->assertExactDataIsPublished($expectedKeyForProduct1, 'table-product.json', self::jsonReplacements(2052));
            $this->assertExactDataIsPublished($expectedKeyForProduct2, 'recliner-product.json', self::jsonReplacements(2050));
            $this->assertExactDataIsPublished($expectedKeyForProduct3, 'sofa-product.json', self::jsonReplacements(2051));
        } finally {
            self::$db->revertProductDummyUpdate($product1, $product2, $product3);
        }
    }

    private static function jsonReplacements(int $productIdInValidationFile) {
        return [
            ' [0-9]{4}' => " $productIdInValidationFile", // in product id
            '-[0-9]{4}' => "-$productIdInValidationFile", // in product slug
            '/.{10,11}_.{16}\.jpg' => '/generated_name.jpg', // Magento generates random names for imported images

            // depending on if prices indexer was executed in Magento - the discounted prices are loaded, otherwise they are loaded with same values as main prices
            '10.01$' => '8.51',
            '10.02$' => '8.52',
            '10.03$' => '8.53'
        ];
    }
}
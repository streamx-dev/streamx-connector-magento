<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\utils\ConfigurationEditUtils;

/**
 * @inheritdoc
 * @UsesProductIndexer
 */
class ProductUpdateTest extends BaseDirectDbEntityUpdateTest {

    /** @test */
    public function shouldPublishSimpleProductEditedDirectlyInDatabase() {
        $this->shouldPublishProductEditedDirectlyInDatabase('Joust Duffle Bag', 'bag');
    }

    /** @test */
    public function shouldPublishSimpleProductEditedDirectlyInDatabaseWithoutAttributes() {
        ConfigurationEditUtils::setIndexedProductAttributes('cost'); // index only an attr that bags don't have (so no attr expected in publish payload)
        try {
            $this->shouldPublishProductEditedDirectlyInDatabase('Joust Duffle Bag', 'bag-no-attributes');
        } finally {
            ConfigurationEditUtils::restoreDefaultIndexedProductAttributes();
        }
    }

    /** @test */
    public function shouldPublishBundleProductEditedDirectlyInDatabase() {
        $regexReplacements = self::$db->isEnterpriseMagento() ? [ // in enterprise magento DB, ID of the bundle product is 46, not 45 as in community version
            '"id": "46",' => '"id": "45",',
            '-46"' => '-45"'
        ] : [];
        $this->shouldPublishProductEditedDirectlyInDatabase('Sprite Yoga Companion Kit', 'bundle', $regexReplacements);
    }

    /** @test */
    public function shouldPublishGroupedProductEditedDirectlyInDatabase() {
        $regexReplacements = self::$db->isEnterpriseMagento() ? [ // in enterprise magento DB, ID of the grouped product is 45, not 46 as in community version
            '"id": "45",' => '"id": "46",',
            '-45"' => '-46"'
        ] : [];
        $this->shouldPublishProductEditedDirectlyInDatabase('Set of Sprite Yoga Straps', 'grouped', $regexReplacements);
    }

    private function shouldPublishProductEditedDirectlyInDatabase(string $productName, string $productNameInValidationFileName, array $regexReplacements = []): void {
        // given
        $productNewName = "Name modified for testing, was $productName";
        $productId = self::$db->getProductId($productName);

        // and
        $expectedKey = self::productKey($productId);
        self::removeFromStreamX($expectedKey);

        // when
        self::$db->renameProduct($productId, $productNewName);

        try {
            // and
            $this->reindexMview();

            // then
            $this->assertExactDataIsPublished($expectedKey, "edited-$productNameInValidationFileName-product.json", $regexReplacements);
        } finally {
            self::$db->renameProduct($productId, $productName);
        }
    }
}
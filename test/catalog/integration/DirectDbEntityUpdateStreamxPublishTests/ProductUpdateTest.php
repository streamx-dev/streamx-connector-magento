<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

/**
 * @inheritdoc
 * @UsesProductIndexer
 */
class ProductUpdateTest extends BaseDirectDbEntityUpdateTest {

    /** @test */
    public function shouldPublishSimpleProductEditedDirectlyInDatabaseToStreamx() {
        $this->shouldPublishProductEditedDirectlyInDatabaseToStreamx('Joust Duffle Bag', 'bag');
    }

    /** @test */
    public function shouldPublishSimpleProductEditedDirectlyInDatabaseToStreamxWithoutAttributes() {
        $this->setConfigurationValue($this->PRODUCT_ATTRIBUTES_PATH, 'cost'); // index only an attr that bags don't have (so no attr expected in publish payload)
        try {
            $this->shouldPublishProductEditedDirectlyInDatabaseToStreamx('Joust Duffle Bag', 'bag-no-attributes');
        } finally {
            $this->restoreConfigurationValue($this->PRODUCT_ATTRIBUTES_PATH);
        }
    }

    /** @test */
    public function shouldPublishBundleProductEditedDirectlyInDatabaseToStreamx() {
        $this->shouldPublishProductEditedDirectlyInDatabaseToStreamx('Sprite Yoga Companion Kit', 'bundle');
    }

    /** @test */
    public function shouldPublishGroupedProductEditedDirectlyInDatabaseToStreamx() {
        // TODO: the produced json doesn't contain information about the components that make up the grouped product
        $this->shouldPublishProductEditedDirectlyInDatabaseToStreamx('Set of Sprite Yoga Straps', 'grouped');
    }

    private function shouldPublishProductEditedDirectlyInDatabaseToStreamx(string $productName, string $productNameInValidationFileName): void {
        // given
        $productNewName = "Name modified for testing, was $productName";
        $productId = self::$db->getProductId($productName);

        // and
        $expectedKey = "pim:$productId";
        self::removeFromStreamX($expectedKey);

        // when
        self::$db->renameProduct($productId, $productNewName);

        try {
            // and
            $this->reindexMview();

            // then
            $this->assertExactDataIsPublished($expectedKey, "edited-$productNameInValidationFileName-product.json");
        } finally {
            self::$db->renameProduct($productId, $productName);
        }
    }
}
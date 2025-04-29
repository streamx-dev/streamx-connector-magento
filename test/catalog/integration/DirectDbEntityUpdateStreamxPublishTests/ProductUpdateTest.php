<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCatalog\test\integration\utils\ConfigurationEditUtils;

/**
 * @inheritdoc
 */
class ProductUpdateTest extends BaseDirectDbEntityUpdateTest {

    const INDEXER_IDS = [ProductProcessor::INDEXER_ID];

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

    /** @test */
    public function shouldNotPublishBundleProduct() {
        // given: publish some dummy data directly at the product key, to later on verify that editing the product doesn't result in publishing it via indexer
        $productName = 'Sprite Yoga Companion Kit';
        $productId = self::$db->getProductId($productName);

        // and
        $productToPublish = ['id' => (string) $productId->getEntityId()];
        $streamxClient = parent::createStreamxClient(self::$store1Id, self::STORE_1_CODE);
        $streamxClient->publish([$productToPublish], ProductProcessor::INDEXER_ID);

        // verify published
        $expectedKey = self::productKey($productId);
        $regexReplacements = self::$db->isEnterpriseMagento() ? [ '46' => '45' ] : []; // in enterprise magento DB, ID of the bundle product is 46, not 45 as in community version
        $this->assertExactDataIsPublished($expectedKey, "dummy-bundle-product.json", $regexReplacements);

        // when
        $productNewName = "Name modified for testing, was $productName";
        self::$db->renameProduct($productId, $productNewName);

        try {
            // and
            $this->reindexMview();

            // then: due to bundle products being not available in configuration - their IDs are treated as not existing by the indexer, so are unpublished
            $this->assertDataIsUnpublished($expectedKey);
        } finally {
            self::$db->renameProduct($productId, $productName);
        }
    }
}
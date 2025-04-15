<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\utils\ConfigurationEditUtils;
use StreamX\ConnectorCatalog\test\integration\utils\EntityIds;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoEndpointsCaller;

/**
 * @inheritdoc
 * @UsesProductIndexer
 */
class ProductUpdateTest extends BaseAppEntityUpdateTest {

    /** @test */
    public function shouldPublishSimpleProductEditedUsingMagentoApplication() {
        $this->shouldPublishProductEditedUsingMagentoApplication('Joust Duffle Bag', 'bag');
    }

    /** @test */
    public function shouldPublishSimpleProductEditedUsingMagentoApplication_WhenRabbitMqIsDisabled() {
        ConfigurationEditUtils::setConfigurationValue(ConfigurationEditUtils::ENABLE_RABBIT_MQ, '0');
        try {
            $this->shouldPublishProductEditedUsingMagentoApplication('Joust Duffle Bag', 'bag');
        } finally {
            ConfigurationEditUtils::setConfigurationValue(ConfigurationEditUtils::ENABLE_RABBIT_MQ, '1');
        }
    }

    /** @test */
    public function shouldPublishSimpleProductEditedUsingMagentoApplicationWithoutAttributes() {
        ConfigurationEditUtils::setIndexedProductAttributes('cost'); // index only an attr that bags don't have (so no attr expected in publish payload)
        try {
            $this->shouldPublishProductEditedUsingMagentoApplication('Joust Duffle Bag', 'bag-no-attributes');
        } finally {
            ConfigurationEditUtils::restoreDefaultIndexedProductAttributes();
        }
    }

    /** @test */
    public function shouldPublishBundleProductEditedUsingMagentoApplication() {
        $regexReplacements = self::$db->isEnterpriseMagento() ? [ // in enterprise magento DB, ID of the bundle product is 46, not 45 as in community version
            '"id": "46",' => '"id": "45",',
            '-46"' => '-45"'
        ] : [];
        $this->shouldPublishProductEditedUsingMagentoApplication('Sprite Yoga Companion Kit', 'bundle', $regexReplacements);
    }

    /** @test */
    public function shouldPublishGroupedProductEditedUsingMagentoApplication() {
        $regexReplacements = self::$db->isEnterpriseMagento() ? [ // in enterprise magento DB, ID of the grouped product is 45, not 46 as in community version
            '"id": "45",' => '"id": "46",',
            '-45"' => '-46"'
        ] : [];
        // TODO: the produced json doesn't contain information about the components that make up the grouped product
        $this->shouldPublishProductEditedUsingMagentoApplication('Set of Sprite Yoga Straps', 'grouped', $regexReplacements);
    }

    private function shouldPublishProductEditedUsingMagentoApplication(string $productName, string $productNameInValidationFileName, array $regexReplacements = []): void {
        // given
        $productNewName = "Name modified for testing, was $productName";
        $productId = self::$db->getProductId($productName);

        // and
        $expectedKey = self::productKey($productId);
        self::removeFromStreamX($expectedKey);

        // when
        self::renameProduct($productId, $productNewName);

        // then
        try {
            $this->assertExactDataIsPublished($expectedKey, "edited-$productNameInValidationFileName-product.json", $regexReplacements);
        } finally {
            self::renameProduct($productId, $productName);
            $this->assertExactDataIsPublished($expectedKey, "original-$productNameInValidationFileName-product.json", $regexReplacements);
        }
    }

    private function renameProduct(EntityIds $productId, string $newName): void {
        MagentoEndpointsCaller::call('product/rename', [
            'productId' => $productId->getEntityId(),
            'newName' => $newName
        ]);
    }
}
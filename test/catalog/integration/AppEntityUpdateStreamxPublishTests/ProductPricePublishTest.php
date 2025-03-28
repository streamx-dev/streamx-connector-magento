<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\utils\ConfigurationEditUtils;
use StreamX\ConnectorCatalog\test\integration\utils\EntityIds;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoEndpointsCaller;

/**
 * @inheritdoc
 * @UsesProductIndexer
 */
class ProductPricePublishTest extends BaseAppEntityUpdateTest {

    /** @test */
    public function shouldPublishProductWithIndexedPrice_WhenUsePricesIndex() {
        // given
        $productId = self::$db->getProductId('Joust Duffle Bag');
        $defaultPrice = self::$db->getDecimalProductAttributeValue($productId, 'price');
        $newPrice = $defaultPrice + 6;

        // and
        $expectedKey = self::productKey($productId);
        self::removeFromStreamX($expectedKey);

        // when
        ConfigurationEditUtils::setConfigurationValue(ConfigurationEditUtils::USE_PRICES_INDEX_PATH, '1');
        $this->changeProductPrice($productId, $newPrice);

        try {
            // then: expecting the old indexed price to be published, since the catalog_product_price built-in indexer didn't run yet to update prices in catalog_product_index_price table
            $this->assertPriceOfPublishedProduct($expectedKey, $defaultPrice);

            // and when: execute the indexer manually
            $this->runPricesIndexer($productId);

            // then: expecting the indexed price to be published, because running catalog_product_price indexer triggers execution of streamx_product_indexer
            $this->assertPriceOfPublishedProduct($expectedKey, $newPrice);
        } finally {
            // restore all changes
            ConfigurationEditUtils::restoreConfigurationValue(ConfigurationEditUtils::USE_PRICES_INDEX_PATH);
            $this->changeProductPrice($productId, $defaultPrice);
            $this->runPricesIndexer($productId);
        }
    }

    private function assertPriceOfPublishedProduct(string $expectedKey, float $expectedPrice): void {
        $publishedProduct = json_decode($this->downloadContentAtKey($expectedKey), true);
        $this->assertEquals($expectedPrice, $publishedProduct['price']['value']);
        $this->assertEquals($expectedPrice, $publishedProduct['price']['discountedValue']);
    }

    private function changeProductPrice(EntityIds $productId, float $newPrice): void {
        $this->changeProductAttributeValue($productId, 'price', $newPrice);
    }

    private function changeProductAttributeValue(EntityIds $productId, string $attributeCode, string $newValue): void {
        MagentoEndpointsCaller::call('product/attribute/change', [
            'productId' => $productId->getEntityId(),
            'attributeCode' => $attributeCode,
            'newValue' => $newValue
        ]);
    }

    private function runPricesIndexer(EntityIds $productId): void {
        // equivalent of running `bin/magento indexer:reindex catalog_product_price`, but optimized to be executed only for a single product
        MagentoEndpointsCaller::call('price/reindex', [
            'productId' => $productId->getEntityId()
        ]);
    }
}
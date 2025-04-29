<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Indexer\ProductIndexer;
use StreamX\ConnectorCatalog\test\integration\utils\ConfigurationEditUtils;
use StreamX\ConnectorCatalog\test\integration\utils\ConfigurationKeyPaths;
use StreamX\ConnectorCatalog\test\integration\utils\EntityIds;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoEndpointsCaller;

/**
 * @inheritdoc
 */
class ProductPricePublishTest extends BaseAppEntityUpdateTest {

    const INDEXER_IDS = [ProductIndexer::INDEXER_ID];

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
        ConfigurationEditUtils::setConfigurationValue(ConfigurationKeyPaths::USE_PRICES_INDEX, '1');
        $this->changeProductPrice($productId, $newPrice);

        try {
            // then: expecting the old indexed price to be published, since the catalog_product_price built-in indexer didn't run yet to update prices in catalog_product_index_price table
            $this->assertExactDataIsPublished($expectedKey, 'original-bag-product.json');

            // and when: execute the indexer manually
            $this->runPricesIndexer($productId);

            // then: expecting the indexed price to be published, because running catalog_product_price indexer triggers execution of streamx_product_indexer
            $this->assertExactDataIsPublished($expectedKey, "edited-bag-product-with-prices-$newPrice-and-$newPrice.json");
        } finally {
            // restore all changes
            ConfigurationEditUtils::restoreConfigurationValue(ConfigurationKeyPaths::USE_PRICES_INDEX);
            $this->changeProductPrice($productId, $defaultPrice);
            $this->runPricesIndexer($productId);
            $this->assertExactDataIsPublished($expectedKey, 'original-bag-product.json');
        }
    }

    private function changeProductPrice(EntityIds $productId, float $newPrice): void {
        MagentoEndpointsCaller::call('product/attribute/change', [
            'productId' => $productId->getEntityId(),
            'attributeCode' => 'price',
            'newValue' => $newPrice
        ]);
    }

    private function runPricesIndexer(EntityIds $productId): void {
        // equivalent of running `bin/magento indexer:reindex catalog_product_price`, but optimized to be executed only for a single product
        MagentoEndpointsCaller::call('price/reindex', [
            'productId' => $productId->getEntityId()
        ]);
    }
}
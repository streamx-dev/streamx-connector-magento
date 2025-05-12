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
class ProductIndexedPricePublishTest extends BaseAppEntityUpdateTest {

    const INDEXER_IDS = [ProductIndexer::INDEXER_ID];

    private const PRICES_INDEXER = 'catalog_product_price';

    private EntityIds $productId;
    private float $defaultPrice;
    private float $newPrice;
    private string $expectedKey;
    private string $originalPriceIndexerMode;

    protected function setUp(): void {
        parent::setUp();

        // load data to be used by all tests
        $this->productId = self::$db->getProductId('Joust Duffle Bag');
        $this->defaultPrice = self::$db->getDecimalProductAttributeValue($this->productId, 'price');
        $this->newPrice = $this->defaultPrice + 6;

        // make sure the product is not on StreamX before test
        $this->expectedKey = self::productKey($this->productId);
        self::removeFromStreamX($this->expectedKey);

        // backup mode of prices indexer (to later restore it)
        $this->originalPriceIndexerMode = parent::getIndexerMode(self::PRICES_INDEXER);

        // switch products indexer to read prices from index
        ConfigurationEditUtils::setConfigurationValue(ConfigurationKeyPaths::USE_PRICES_INDEX, '1');

        // prepare initial state: load default price to prices index
        $this->runPricesIndexer($this->productId);

        // expect product with old (indexed) price to be published, because running prices indexer triggers running products indexer
        $this->assertExactDataIsPublished($this->expectedKey, 'original-bag-product.json');
    }

    protected function tearDown(): void {
        // restore settings
        ConfigurationEditUtils::restoreConfigurationValue(ConfigurationKeyPaths::USE_PRICES_INDEX);
        parent::setIndexerMode(self::PRICES_INDEXER, $this->originalPriceIndexerMode);

        // restore default price
        $this->changeProductPrice($this->productId, $this->defaultPrice);
        $this->runPricesIndexer($this->productId);
        $this->assertExactDataIsPublished($this->expectedKey, 'original-bag-product.json');

        parent::tearDown();
    }

    /** @test */
    public function shouldPublishProductWithIndexedPrice_WhenUsePricesIndex_AndPricesIndexerIsInUpdateOnSaveMode() {
        // given
        parent::setIndexerMode(self::PRICES_INDEXER, parent::UPDATE_ON_SAVE);

        // when
        $this->changeProductPrice($this->productId, $this->newPrice);

        // then: expecting the indexed price to be published, because changing product price triggers prices indexer (when it's in Update On Save mode), and this in turn triggers execution of products indexer
        $this->assertExactDataIsPublished($this->expectedKey, "edited-bag-product-with-prices-$this->newPrice-and-$this->newPrice.json");
    }

    /** @test */
    public function shouldPublishProductWithIndexedPrice_WhenUsePricesIndex_AndPricesIndexerIsInUpdateByScheduleMode() {
        // given
        parent::setIndexerMode(self::PRICES_INDEXER, parent::UPDATE_BY_SCHEDULE);

        // when
        $this->changeProductPrice($this->productId, $this->newPrice);

        // then: expecting the old indexed price to still be published, since the prices indexer didn't run yet to update prices in catalog_product_index_price table
        $this->assertExactDataIsPublished($this->expectedKey, 'original-bag-product.json');

        // and when: execute prices indexer manually
        $this->runPricesIndexer($this->productId);

        // then: expecting the indexed price to be published, because running prices indexer triggers running products indexer
        $this->assertExactDataIsPublished($this->expectedKey, "edited-bag-product-with-prices-$this->newPrice-and-$this->newPrice.json");
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
<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\utils\ConfigurationEditUtils;
use StreamX\ConnectorCatalog\test\integration\utils\EntityIds;

/**
 * @inheritdoc
 * @UsesProductIndexer
 */
class ProductPricePublishTest extends BaseDirectDbEntityUpdateTest {

    private EntityIds $productId;
    private float $defaultPrice;
    private string $expectedKey;

    protected function setUp(): void {
        parent::setUp();
        $this->productId = self::$db->getProductId('Joust Duffle Bag');
        $this->defaultPrice = self::$db->getDecimalProductAttributeValue($this->productId, 'price');

        $this->expectedKey = self::productKey($this->productId);
        self::removeFromStreamX($this->expectedKey);
    }

    protected function tearDown(): void {
        parent::tearDown();
        $this->restoreProductPrice();
    }

    /** @test */
    public function shouldPublishProductWithPriceEditedDirectlyInDatabase() {
        // given
        $newPrice = $this->defaultPrice + 6;

        // when
        $this->changeProductPrice($newPrice);

        // and
        $this->reindexMview();

        // then
        $this->assertPriceAndDiscountedPriceOfPublishedProduct($newPrice, $newPrice);
    }

    /** @test */
    public function shouldPublishProductWithIndexedPrice_WhenUsePricesIndex() {
        // given
        $newPrice = $this->defaultPrice + 6;

        // when
        ConfigurationEditUtils::setConfigurationValue(ConfigurationEditUtils::USE_PRICES_INDEX_PATH, '1');
        $this->changeProductPrice($newPrice);

        try {
            // and
            $this->reindexMview();

            // then: expecting the old indexed price to be published, since the catalog_product_price Magento built-in indexer didn't run yet to update prices in catalog_product_index_price table
            $this->assertPriceAndDiscountedPriceOfPublishedProduct($this->defaultPrice, $this->defaultPrice);
        } finally {
            ConfigurationEditUtils::restoreConfigurationValue(ConfigurationEditUtils::USE_PRICES_INDEX_PATH);
        }
    }

    /** @test */
    public function shouldPublishProductWithCatalogRulePrice() {
        // given
        $catalogRulePrice = $this->defaultPrice - 5;

        // when
        ConfigurationEditUtils::setConfigurationValues([
            ConfigurationEditUtils::USE_PRICES_INDEX_PATH => '1',
            ConfigurationEditUtils::USE_CATALOG_PRICE_RULES_PATH => '1'
        ]);
        $this->insertCatalogRulePrice($this->productId, $catalogRulePrice, self::$website1Id);
        self::$db->productDummyUpdate($this->productId);

        try {
            // and
            $this->reindexMview();

            // then: expecting the catalog rule price to be published as discounted price
            $this->assertPriceAndDiscountedPriceOfPublishedProduct($this->defaultPrice, $catalogRulePrice);
        } finally {
            self::$db->revertProductDummyUpdate($this->productId);
            $this->deleteCatalogRulePrice();
            ConfigurationEditUtils::restoreConfigurationValues([
                ConfigurationEditUtils::USE_PRICES_INDEX_PATH,
                ConfigurationEditUtils::USE_CATALOG_PRICE_RULES_PATH,
            ]);
        }
    }

    private function assertPriceAndDiscountedPriceOfPublishedProduct(float $expectedPrice, float $expectedDiscountedPrice): void {
        $publishedProduct = json_decode($this->downloadContentAtKey($this->expectedKey), true);
        $this->assertEquals($expectedPrice, $publishedProduct['price']['value']);
        $this->assertEquals($expectedDiscountedPrice, $publishedProduct['price']['discountedValue']);
    }

    private function changeProductPrice(float $newPrice): void {
        $priceAttributeId = self::$db->getProductAttributeId('price');
        self::$db->insertDecimalProductAttribute($this->productId, $priceAttributeId, $newPrice);
    }

    private function restoreProductPrice(): void {
        $this->changeProductPrice($this->defaultPrice);
    }

    private function insertCatalogRulePrice(EntityIds $productId, float $catalogRulePrice, int $websiteId): void {
        $ruleDate = self::$db->selectSingleValue("SELECT website_date FROM catalog_product_index_website WHERE website_id = $websiteId");
        $productEntityId = $productId->getEntityId();
        self::$db->insert("
            INSERT INTO catalogrule_product_price(rule_date, customer_group_id, product_id, rule_price, website_id)
                                           VALUES('$ruleDate', 0, $productEntityId, $catalogRulePrice, $websiteId)
        ");
    }

    private function deleteCatalogRulePrice(): void {
        self::$db->deleteLastRow('catalogrule_product_price', 'rule_product_price_id');
    }
}
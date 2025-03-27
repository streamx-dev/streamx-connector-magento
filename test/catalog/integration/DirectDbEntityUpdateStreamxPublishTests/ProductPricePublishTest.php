<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\test\integration\utils\ConfigurationEditUtils;
use StreamX\ConnectorCatalog\test\integration\utils\EntityIds;

/**
 * @inheritdoc
 * @UsesProductIndexer
 */
class ProductPricePublishTest extends BaseDirectDbEntityUpdateTest {

    /** @test */
    public function shouldPublishProductWithPriceEditedDirectlyInDatabase() {
        // given
        $productId = self::$db->getProductId('Joust Duffle Bag');
        $defaultPrice = self::$db->getDecimalProductAttributeValue($productId, 'price');
        $newPrice = $defaultPrice + 6;

        // and
        $expectedKey = self::productKey($productId);
        self::removeFromStreamX($expectedKey);

        // when
        $this->changePriceOfProduct($productId, $newPrice);

        try {
            // and
            $this->reindexMview();

            // then
            $publishedProduct = json_decode($this->downloadContentAtKey($expectedKey), true);
            $this->assertPrice($publishedProduct, $newPrice);
            $this->assertDiscountedPrice($publishedProduct, $newPrice);
        } finally {
            $this->changePriceOfProduct($productId, $defaultPrice);
        }
    }

    /** @test */
    public function shouldPublishProductWithPriceEditedDirectlyInDatabase_WhenUsePricesIndex() {
        // given
        $productId = self::$db->getProductId('Joust Duffle Bag');
        $defaultPrice = self::$db->getDecimalProductAttributeValue($productId, 'price');
        $newPrice = $defaultPrice + 6;

        // and
        $expectedKey = self::productKey($productId);
        self::removeFromStreamX($expectedKey);

        // when
        ConfigurationEditUtils::setConfigurationValue(ConfigurationEditUtils::USE_PRICES_INDEX_PATH, '1');
        $this->changePriceOfProduct($productId, $newPrice);

        try {
            // and
            $this->reindexMview();

            // then
            $publishedProduct = json_decode($this->downloadContentAtKey($expectedKey), true);
            // TODO: change main impl so that the edited $newPrice is published instead of $defaultPrice also when using prices index
            $this->assertPrice($publishedProduct, $defaultPrice);
            $this->assertDiscountedPrice($publishedProduct, $defaultPrice);
        } finally {
            $this->changePriceOfProduct($productId, $defaultPrice);
            ConfigurationEditUtils::restoreConfigurationValue(ConfigurationEditUtils::USE_PRICES_INDEX_PATH);
        }
    }

    /** @test */
    public function shouldPublishProductWithCatalogRulePrice() {
        // given
        $productId = self::$db->getProductId('Joust Duffle Bag');
        $defaultPrice = self::$db->getDecimalProductAttributeValue($productId, 'price');
        $catalogRulePrice = $defaultPrice - 5;

        // and
        $expectedKey = self::productKey($productId);
        self::removeFromStreamX($expectedKey);

        // when
        ConfigurationEditUtils::setConfigurationValues([
            ConfigurationEditUtils::USE_PRICES_INDEX_PATH => '1',
            ConfigurationEditUtils::USE_CATALOG_PRICE_RULES_PATH => '1'
        ]);
        $this->insertCatalogRulePrice($productId, $catalogRulePrice, self::$website1Id);
        self::$db->productDummyUpdate($productId);

        try {
            // and
            $this->reindexMview();

            // then
            $publishedProduct = json_decode($this->downloadContentAtKey($expectedKey), true);
            $this->assertPrice($publishedProduct, $defaultPrice);
            $this->assertDiscountedPrice($publishedProduct, $catalogRulePrice);
        } finally {
            self::$db->revertProductDummyUpdate($productId);
            $this->deleteCatalogRulePrice();
            ConfigurationEditUtils::restoreConfigurationValues([
                ConfigurationEditUtils::USE_PRICES_INDEX_PATH,
                ConfigurationEditUtils::USE_CATALOG_PRICE_RULES_PATH,
            ]);
        }
    }

    private function assertPrice(array $product, float $expectedPrice): void {
        $this->assertEquals($expectedPrice, $product['price']['value']);
    }

    private function assertDiscountedPrice(array $product, float $expectedPrice): void {
        $this->assertEquals($expectedPrice, $product['price']['discountedValue']);
    }

    private function changePriceOfProduct(EntityIds $productId, float $newPrice): void {
        $priceAttributeId = self::$db->getProductAttributeId('price');
        self::$db->insertDecimalProductAttribute($productId, $priceAttributeId, $newPrice);
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
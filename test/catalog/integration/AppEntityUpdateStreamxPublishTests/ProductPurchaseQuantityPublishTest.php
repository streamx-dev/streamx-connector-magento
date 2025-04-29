<?php

namespace StreamX\ConnectorCatalog\test\integration\AppEntityUpdateStreamxPublishTests;

use StreamX\ConnectorCatalog\Indexer\ProductIndexer;
use StreamX\ConnectorCatalog\test\integration\utils\EntityIds;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoEndpointsCaller;

/**
 * @inheritdoc
 */
class ProductPurchaseQuantityPublishTest extends BaseAppEntityUpdateTest {

    const INDEXER_IDS = [ProductIndexer::INDEXER_ID];

    /** @test */
    public function shouldPublishProductWithDecreasedQuantity_WhenProductIsPurchasedByCustomer() {
        // given
        $productId = self::$db->getProductId('Joust Duffle Bag');
        $defaultQuantity = self::getQuantity($productId);

        $this->assertEquals(100, $defaultQuantity);
        $quantityToPurchase = 3;

        // and
        $expectedKey = self::productKey($productId);
        self::removeFromStreamX($expectedKey);

        // when
        $this->purchaseProduct($productId, $quantityToPurchase);

        try {
            $this->assertExactDataIsPublished($expectedKey, 'edited-quantity-bag-product.json');
        } finally {
            self::setQuantity($productId, $defaultQuantity);
        }
    }

    private function purchaseProduct(EntityIds $productId, int $quantity): void {
        MagentoEndpointsCaller::call('product/purchase', [
            'productId' => $productId->getEntityId(),
            'quantity' => $quantity
        ]);
    }

    private static function getQuantity(EntityIds $productId): int {
        return self::$db->selectSingleValue("
            SELECT qty FROM cataloginventory_stock_status WHERE product_id = {$productId->getEntityId()}
        ");
    }

    private static function setQuantity(EntityIds $productId, int $quantity): void {
        self::$db->executeQueries("
            UPDATE cataloginventory_stock_status SET qty = $quantity WHERE product_id = {$productId->getEntityId()}
        ", "
            UPDATE cataloginventory_stock_item SET qty = $quantity WHERE product_id = {$productId->getEntityId()}
        ");
    }
}
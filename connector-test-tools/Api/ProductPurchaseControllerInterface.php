<?php

namespace StreamX\ConnectorTestTools\Api;

interface ProductPurchaseControllerInterface {

    /**
     * Purchases a product
     * @param int $productId The product to purchase
     * @param int $quantity Quantity of the product to be purchased
     * @return void
     */
    public function purchaseProduct(int $productId, int $quantity): void;
}

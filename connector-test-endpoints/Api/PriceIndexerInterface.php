<?php

namespace StreamX\ConnectorTestEndpoints\Api;

interface PriceIndexerInterface {

    /**
     * Triggers reindexing Magento's built-in catalog_product_price indexer for a single product
     * @param int $productId The product to reindex
     * @return void
     */
    public function reindexPrice(int $productId): void;
}

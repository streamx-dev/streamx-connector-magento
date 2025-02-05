<?php

namespace StreamX\ConnectorCatalog\Plugin\Indexer\CatalogInventory;

use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use Magento\CatalogInventory\Observer\ReindexQuoteInventoryObserver;

class ReindexQuoteInventoryObserverPlugin
{
    private ProductsForReindex $productsForReindex;
    private ProductProcessor $productProcessor;

    public function __construct(
        ProductsForReindex $itemsForReindex,
        ProductProcessor $processor
    ) {
        $this->productsForReindex = $itemsForReindex;
        $this->productProcessor = $processor;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    // TODO review this code, add tests
    public function afterExecute(ReindexQuoteInventoryObserver $subject): void
    {
        $products = $this->productsForReindex->getProducts();

        if (!empty($products)) {
            $this->productProcessor->reindexList($products);
            $this->productsForReindex->clear();
        }
    }
}

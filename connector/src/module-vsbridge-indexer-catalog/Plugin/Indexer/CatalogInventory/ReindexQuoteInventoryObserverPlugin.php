<?php

namespace Divante\VsbridgeIndexerCatalog\Plugin\Indexer\CatalogInventory;

use Divante\VsbridgeIndexerCatalog\Model\Indexer\ProductProcessor;
use Magento\CatalogInventory\Observer\ReindexQuoteInventoryObserver;

class ReindexQuoteInventoryObserverPlugin
{
    /**
     * @var ProductsForReindex
     */
    private $productsForReindex;

    /**
     * @var ProductProcessor
     */
    private $productProcessor;

    /**
     * ProcessStockChangedPlugin constructor.
     *
     * @param ProductsForReindex $itemsForReindex
     * @param ProductProcessor $processor
     */
    public function __construct(
        ProductsForReindex $itemsForReindex,
        ProductProcessor $processor
    ) {
        $this->productsForReindex = $itemsForReindex;
        $this->productProcessor = $processor;
    }

    /**
     * @param ReindexQuoteInventoryObserver $subject
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @return void
     */
    public function afterExecute(ReindexQuoteInventoryObserver $subject)
    {
        $products = $this->productsForReindex->getProducts();

        if (!empty($products)) {
            $this->productProcessor->reindexList($products);
            $this->productsForReindex->clear();
        }
    }
}

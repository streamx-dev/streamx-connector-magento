<?php

namespace Divante\VsbridgeIndexerCatalog\Plugin\Indexer\CatalogInventory;

use Divante\VsbridgeIndexerCatalog\Model\Indexer\ProductProcessor;
use Magento\CatalogInventory\Observer\RevertQuoteInventoryObserver;

class RevertQuoteInventoryObserverPlugin
{
    /**
     * @var ProductsForReindex
     */
    private $productsForReindex;

    /**
     * @var ProductProcessor
     */
    private $productProcessor;

    public function __construct(
        ProductsForReindex $itemsForReindex,
        ProductProcessor $processor
    ) {
        $this->productsForReindex = $itemsForReindex;
        $this->productProcessor = $processor;
    }

    /**
     * @param RevertQuoteInventoryObserver $subject
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @return void
     */
    public function afterExecute(RevertQuoteInventoryObserver $subject)
    {
        $products = $this->productsForReindex->getProducts();

        if (!empty($products)) {
            $this->productProcessor->reindexList($products);
            $this->productsForReindex->clear();
        }
    }
}

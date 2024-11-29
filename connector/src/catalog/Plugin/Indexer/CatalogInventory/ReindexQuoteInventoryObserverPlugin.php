<?php

namespace StreamX\ConnectorCatalog\Plugin\Indexer\CatalogInventory;

use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
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

    public function __construct(
        ProductsForReindex $itemsForReindex,
        ProductProcessor $processor
    ) {
        $this->productsForReindex = $itemsForReindex;
        $this->productProcessor = $processor;
    }

    /**
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

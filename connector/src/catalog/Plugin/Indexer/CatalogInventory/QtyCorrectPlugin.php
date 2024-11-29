<?php

namespace Divante\VsbridgeIndexerCatalog\Plugin\Indexer\CatalogInventory;

use Magento\CatalogInventory\Model\ResourceModel\QtyCounterInterface;

class QtyCorrectPlugin
{
    /**
     * @var ProductsForReindex
     */
    private $productsForReindex;

    public function __construct(
        ProductsForReindex $itemsForReindex
    ) {
        $this->productsForReindex = $itemsForReindex;
    }

    /**
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundCorrectItemsQty(
        QtyCounterInterface $subject,
        callable $proceed,
        array $items,
        int $websiteId,
        string $operator
    ) {
        if (!empty($items)) {
            $productIds = array_keys($items);
            $this->productsForReindex->setProducts($productIds);
        }

        $proceed($items, $websiteId, $operator);
    }
}

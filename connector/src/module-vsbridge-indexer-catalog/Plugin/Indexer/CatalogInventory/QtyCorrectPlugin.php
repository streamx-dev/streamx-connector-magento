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
     * @param QtyCounterInterface $subject
     * @param callable $proceed
     * @param int $websiteId
     * @param string $operator
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundCorrectItemsQty(
        QtyCounterInterface $subject,
        callable $proceed,
        array $items,
        $websiteId,
        $operator
    ) {
        if (!empty($items)) {
            $productIds = array_keys($items);
            $this->productsForReindex->setProducts($productIds);
        }

        $proceed($items, $websiteId, $operator);
    }
}

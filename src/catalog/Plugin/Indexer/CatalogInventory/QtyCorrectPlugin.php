<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Plugin\Indexer\CatalogInventory;

use Magento\CatalogInventory\Model\ResourceModel\QtyCounterInterface;

class QtyCorrectPlugin
{
    private ProductsForReindex $productsForReindex;

    public function __construct(ProductsForReindex $itemsForReindex)
    {
        $this->productsForReindex = $itemsForReindex;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    // TODO review this code, add tests
    public function aroundCorrectItemsQty(
        QtyCounterInterface $subject,
        callable $proceed,
        array $items,
        int $websiteId,
        string $operator
    ): void {
        if (!empty($items)) {
            $productIds = array_keys($items);
            $this->productsForReindex->setProducts($productIds);
        }

        $proceed($items, $websiteId, $operator);
    }
}

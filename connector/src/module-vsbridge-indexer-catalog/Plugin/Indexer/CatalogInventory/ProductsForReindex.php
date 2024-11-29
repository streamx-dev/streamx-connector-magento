<?php

namespace Divante\VsbridgeIndexerCatalog\Plugin\Indexer\CatalogInventory;

class ProductsForReindex
{
    /**
     * @var array
     */
    private $productsForReindex = [];

    /**
     * @param array $items
     * @return void
     */
    public function setProducts(array $items)
    {
        $this->productsForReindex = $items;
    }

    /**
     * @return array
     */
    public function getProducts()
    {
        return $this->productsForReindex;
    }

    /**
     * @return void
     */
    public function clear()
    {
        $this->productsForReindex = [];
    }
}

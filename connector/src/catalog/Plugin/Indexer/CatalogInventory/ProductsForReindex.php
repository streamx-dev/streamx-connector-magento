<?php

namespace StreamX\ConnectorCatalog\Plugin\Indexer\CatalogInventory;

class ProductsForReindex
{
    /**
     * @var array
     */
    private $productsForReindex = [];

    /**
     * @return void
     */
    public function setProducts(array $items)
    {
        $this->productsForReindex = $items;
    }

    public function getProducts(): array
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

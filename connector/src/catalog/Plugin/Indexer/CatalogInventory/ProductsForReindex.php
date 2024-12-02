<?php

namespace StreamX\ConnectorCatalog\Plugin\Indexer\CatalogInventory;

class ProductsForReindex
{
    /**
     * @var array
     */
    private $productsForReindex = [];

    public function setProducts(array $items): void
    {
        $this->productsForReindex = $items;
    }

    public function getProducts(): array
    {
        return $this->productsForReindex;
    }

    public function clear(): void
    {
        $this->productsForReindex = [];
    }
}

<?php

namespace Divante\VsbridgeIndexerCatalog\Api\ArrayConverter\Product;

interface InventoryConverterInterface
{
    /**
     * @param int $storeId
     * @param array $inventory
     *
     * @return array
     */
    public function prepareInventoryData(int $storeId, array $inventory): array;
}

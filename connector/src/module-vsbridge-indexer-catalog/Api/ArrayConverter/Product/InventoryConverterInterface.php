<?php

namespace Divante\VsbridgeIndexerCatalog\Api\ArrayConverter\Product;

interface InventoryConverterInterface
{
    public function prepareInventoryData(int $storeId, array $inventory): array;
}

<?php

namespace StreamX\ConnectorCatalog\Api\ArrayConverter\Product;

interface InventoryConverterInterface
{
    public function prepareInventoryData(int $storeId, array $inventory): array;
}

<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use StreamX\ConnectorCatalog\Api\LoadInventoryInterface;
use StreamX\ConnectorCore\Api\DataProviderInterface;

class Inventory implements DataProviderInterface
{
    private LoadInventoryInterface $getInventory;

    public function __construct(LoadInventoryInterface $getInventory)
    {
        $this->getInventory = $getInventory;
    }

    public function addData(array $indexData, int $storeId): array
    {
        $inventoryData = $this->getInventory->execute($indexData, $storeId);

        foreach ($inventoryData as $inventoryDataRow) {
            $productId = (int) $inventoryDataRow['product_id'];
            $indexData[$productId]['stock'] = $inventoryDataRow;
        }

        $inventoryData = null;

        return $indexData;
    }
}

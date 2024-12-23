<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use StreamX\ConnectorCatalog\Api\LoadInventoryInterface;
use StreamX\ConnectorCatalog\Api\ArrayConverter\Product\InventoryConverterInterface;
use StreamX\ConnectorCore\Api\DataProviderInterface;

class Inventory implements DataProviderInterface
{
    private LoadInventoryInterface $getInventory;
    private InventoryConverterInterface $inventoryProcessor;

    public function __construct(
        LoadInventoryInterface $getInventory,
        InventoryConverterInterface $inventoryProcessor
    ) {
        $this->getInventory = $getInventory;
        $this->inventoryProcessor = $inventoryProcessor;
    }

    public function addData(array $indexData, int $storeId): array
    {
        $inventoryData = $this->getInventory->execute($indexData, $storeId);

        foreach ($inventoryData as $inventoryDataRow) {
            $productId = (int) $inventoryDataRow['product_id'];
            $indexData[$productId]['stock'] =
                $this->inventoryProcessor->prepareInventoryData($storeId, $inventoryDataRow);
        }

        $inventoryData = null;

        return $indexData;
    }
}

<?php

namespace Divante\VsbridgeIndexerCatalog\Model\Indexer\DataProvider\Product;

use Divante\VsbridgeIndexerCatalog\Api\LoadInventoryInterface;
use Divante\VsbridgeIndexerCatalog\Api\ArrayConverter\Product\InventoryConverterInterface;
use Divante\VsbridgeIndexerCore\Api\DataProviderInterface;

class Inventory implements DataProviderInterface
{

    /**
     * @var LoadInventoryInterface
     */
    private $getInventory;

    /**
     * @var InventoryConverterInterface
     */
    private $inventoryProcessor;

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

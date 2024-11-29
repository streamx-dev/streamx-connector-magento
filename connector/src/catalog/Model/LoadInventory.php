<?php


declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model;

use StreamX\ConnectorCatalog\Api\LoadInventoryInterface;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\Inventory as InventoryResource;

class LoadInventory implements LoadInventoryInterface
{
    /**
     * @var InventoryResource
     */
    private $resource;

    public function __construct(InventoryResource $resource)
    {
        $this->resource = $resource;
    }

    public function execute(array $indexData, int $storeId): array
    {
        $productIds = array_keys($indexData);

        return $this->resource->loadInventory($productIds);
    }
}

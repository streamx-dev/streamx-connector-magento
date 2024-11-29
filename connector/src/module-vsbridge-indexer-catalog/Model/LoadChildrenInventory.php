<?php


declare(strict_types=1);

namespace Divante\VsbridgeIndexerCatalog\Model;

use Divante\VsbridgeIndexerCatalog\Api\LoadInventoryInterface;
use Divante\VsbridgeIndexerCatalog\Model\ResourceModel\Product\Inventory as InventoryResource;

class LoadChildrenInventory implements LoadInventoryInterface
{
    /**
     * @var InventoryResource
     */
    private $resource;

    /**
     * LoadChildrenInventory constructor.
     *
     * @param InvetoryResource $resource
     */
    public function __construct(InventoryResource $resource)
    {
        $this->resource = $resource;
    }

    public function execute(array $indexData, int $storeId): array
    {
        $productIds = array_keys($indexData);

        return $this->resource->loadChildrenInventory($productIds);
    }
}

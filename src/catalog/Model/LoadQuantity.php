<?php

declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model;

use Magento\Framework\App\ResourceConnection;
use StreamX\ConnectorCatalog\Api\LoadQuantityInterface;

class LoadQuantity implements LoadQuantityInterface
{
    private ResourceConnection $resource;

    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    public function execute(array $indexData, int $storeId): array
    {
        $productIds = array_keys($indexData);
        $connection = $this->resource->getConnection();

        $select = $connection->select()
            ->from($this->resource->getTableName('cataloginventory_stock_item'))
            ->columns(['product_id', 'qty'])
            ->where('product_id IN (?)', $productIds);

        return $connection->fetchAssoc($select);
    }
}

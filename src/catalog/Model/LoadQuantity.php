<?php

declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model;

use Magento\Framework\App\ResourceConnection;

class LoadQuantity
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
        $tableName = $this->resource->getTableName('cataloginventory_stock_item');

        $select = $connection->select()
            ->from($tableName, ['product_id', 'qty'])
            ->where('product_id IN (?)', $productIds);

        return $connection->fetchAssoc($select);
    }
}

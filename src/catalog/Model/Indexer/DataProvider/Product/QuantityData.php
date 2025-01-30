<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use Magento\Framework\App\ResourceConnection;
use StreamX\ConnectorCore\Api\DataProviderInterface;

class QuantityData extends DataProviderInterface
{
    private ResourceConnection $resource;

    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    public function addData(array $indexData, int $storeId): array
    {
        $quantityDataRows = $this->loadQuantityDataRows($indexData);

        foreach ($quantityDataRows as $quantityDataRow) {
            $productId = (int)$quantityDataRow['product_id'];
            $quantity = (float)$quantityDataRow['qty'];
            $indexData[$productId]['quantity'] = $quantity;
        }

        return $indexData;
    }

    private function loadQuantityDataRows(array $indexData): array
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

<?php


declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Product;

use StreamX\ConnectorCatalog\Model\Inventory\Fields as InventoryFields;
use Magento\Framework\App\ResourceConnection;
use Magento\CatalogInventory\Api\StockConfigurationInterface;

class Inventory
{
    private StockConfigurationInterface $stockConfiguration;
    private ResourceConnection $resource;
    private InventoryFields $inventoryFields;

    public function __construct(
        StockConfigurationInterface $stockConfiguration,
        InventoryFields $fields,
        ResourceConnection $resourceModel
    ) {
        $this->inventoryFields = $fields;
        $this->resource = $resourceModel;
        $this->stockConfiguration = $stockConfiguration;
    }

    public function loadInventory(array $productIds): array
    {
        return $this->getInventoryData($productIds, $this->inventoryFields->getRequiredColumns());
    }

    public function loadChildrenInventory(array $productIds): array
    {
        return $this->getInventoryData($productIds, $this->inventoryFields->getChildRequiredColumns());
    }

    private function getInventoryData(array $productIds, array $fields): array
    {
        $websiteId = $this->getWebsiteId();
        $connection = $this->resource->getConnection();

        $select = $connection->select()
            ->from(
                ['main_table' => $this->resource->getTableName('cataloginventory_stock_item')],
                $fields
            )->where('main_table.product_id IN (?)', $productIds);

        $joinConditionClause = [
            'main_table.product_id=status_table.product_id',
            'main_table.stock_id=status_table.stock_id',
            'status_table.website_id = ?'
        ];

        $select->joinLeft(
            ['status_table' => $this->resource->getTableName('cataloginventory_stock_status')],
            $connection->quoteInto(
                implode(' AND ', $joinConditionClause),
                $websiteId
            ),
            ['stock_status']
        );

        return $connection->fetchAssoc($select);
    }

    /**
     * @return int|null
     */
    private function getWebsiteId()
    {
        return $this->stockConfiguration->getDefaultScopeId();
    }
}

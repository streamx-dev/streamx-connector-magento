<?php


declare(strict_types=1);

namespace Divante\VsbridgeIndexerCatalog\Model\ResourceModel\Product;

use Divante\VsbridgeIndexerCatalog\Model\Inventory\Fields as InventoryFields;
use Magento\Framework\App\ResourceConnection;
use Magento\CatalogInventory\Api\StockConfigurationInterface;

class Inventory
{
    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var InventoryFields
     */
    private $inventoryFields;

    public function __construct(
        StockConfigurationInterface $stockConfiguration,
        InventoryFields $fields,
        ResourceConnection $resourceModel
    ) {
        $this->inventoryFields = $fields;
        $this->resource = $resourceModel;
        $this->stockConfiguration = $stockConfiguration;
    }

    /**
     * @return array
     */
    public function loadInventory(array $productIds): array
    {
        return $this->getInventoryData($productIds, $this->inventoryFields->getRequiredColumns());
    }

    /**
     * @return array
     */
    public function loadChildrenInventory(array $productIds): array
    {
        return $this->getInventoryData($productIds, $this->inventoryFields->getChildRequiredColumns());
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
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

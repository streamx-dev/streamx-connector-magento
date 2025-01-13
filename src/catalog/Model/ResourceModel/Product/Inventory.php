<?php


declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Product;

use Magento\Framework\App\ResourceConnection;
use Magento\CatalogInventory\Api\StockConfigurationInterface;

class Inventory
{
    private const requiredFields = [ 'product_id', 'qty' ];

    private StockConfigurationInterface $stockConfiguration;
    private ResourceConnection $resource;

    public function __construct(
        StockConfigurationInterface $stockConfiguration,
        ResourceConnection $resourceModel
    ) {
        $this->resource = $resourceModel;
        $this->stockConfiguration = $stockConfiguration;
    }

    public function loadInventory(array $productIds): array
    {
        $websiteId = $this->getWebsiteId();
        $connection = $this->resource->getConnection();

        $select = $connection->select()
            ->from(
                ['main_table' => $this->resource->getTableName('cataloginventory_stock_item')],
                self::requiredFields
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

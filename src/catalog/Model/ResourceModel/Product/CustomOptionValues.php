<?php


declare(strict_types = 1);

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Product;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\Store;

class CustomOptionValues
{
    private ResourceConnection $resource;

    public function __construct(ResourceConnection $resourceModel)
    {
        $this->resource = $resourceModel;
    }

    public function loadOptionValues(array $optionIds, int $storeId): array
    {
        $select = $this->getProductOptionSelect($optionIds, $storeId);

        return $this->getConnection()->fetchAll($select);
    }

    private function getProductOptionSelect(array $optionIds, int $storeId): Select
    {
        $connection = $this->getConnection();
        $mainTableAlias = 'main_table';

        $select = $connection->select()->from(
            [$mainTableAlias => $this->resource->getTableName('catalog_product_option_type_value')]
        );

        $select->where($mainTableAlias.  '.option_id IN (?)', $optionIds);

        $this->addTitleToResult($select, $storeId);
        $this->addPriceToResult($select, $storeId);

        $select->order('sort_order ASC');
        $select->order('title ASC');

        return $select;
    }

    private function addTitleToResult(Select $select, int $storeId): void
    {
        $optionTitleTable = $this->resource->getTableName('catalog_product_option_type_title');
        $titleExpr = $this->getConnection()->getCheckSql(
            'store_value_title.title IS NULL',
            'default_value_title.title',
            'store_value_title.title'
        );

        $joinExpr = 'store_value_title.option_type_id = main_table.option_type_id AND ' .
            $this->getConnection()->quoteInto('store_value_title.store_id = ?', $storeId);
        $select->join(
            ['default_value_title' => $optionTitleTable],
            'default_value_title.option_type_id = main_table.option_type_id',
            ['default_title' => 'title']
        )->joinLeft(
            ['store_value_title' => $optionTitleTable],
            $joinExpr,
            ['store_title' => 'title', 'title' => $titleExpr]
        )->where(
            'default_value_title.store_id = ?',
            Store::DEFAULT_STORE_ID
        );
    }

    private function addPriceToResult(Select $select, int $storeId): void
    {
        $optionTypeTable = $this->resource->getTableName('catalog_product_option_type_price');
        $priceExpr = $this->getConnection()->getCheckSql(
            'store_value_price.price IS NULL',
            'default_value_price.price',
            'store_value_price.price'
        );
        $priceTypeExpr = $this->getConnection()->getCheckSql(
            'store_value_price.price_type IS NULL',
            'default_value_price.price_type',
            'store_value_price.price_type'
        );

        $joinExprDefault = 'default_value_price.option_type_id = main_table.option_type_id AND ' .
            $this->getConnection()->quoteInto(
                'default_value_price.store_id = ?',
                Store::DEFAULT_STORE_ID
            );
        $joinExprStore = 'store_value_price.option_type_id = main_table.option_type_id AND ' .
            $this->getConnection()->quoteInto('store_value_price.store_id = ?', $storeId);
        $select->joinLeft(
            ['default_value_price' => $optionTypeTable],
            $joinExprDefault,
            ['default_price' => 'price', 'default_price_type' => 'price_type']
        )->joinLeft(
            ['store_value_price' => $optionTypeTable],
            $joinExprStore,
            [
                'store_price' => 'price',
                'store_price_type' => 'price_type',
                'price' => $priceExpr,
                'price_type' => $priceTypeExpr
            ]
        );
    }

    private function getConnection(): AdapterInterface
    {
        return $this->resource->getConnection();
    }
}

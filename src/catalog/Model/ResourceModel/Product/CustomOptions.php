<?php declare(strict_types = 1);

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Product;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\Store;

class CustomOptions
{
    private ResourceConnection $resource;

    public function __construct(ResourceConnection $resourceModel)
    {
        $this->resource = $resourceModel;
    }

    public function loadProductOptions(array $linkFieldIds, int $storeId): array
    {
        $select = $this->getProductOptionSelect($linkFieldIds, $storeId);

        return $this->getConnection()->fetchAssoc($select);
    }

    private function getProductOptionSelect(array $linkFieldIds, int $storeId): Select
    {
        $connection = $this->getConnection();
        $mainTableAlias = 'main_table';

        $select = $connection->select()->from(
            [$mainTableAlias => $this->resource->getTableName('catalog_product_option')]
        );

        $select->where($mainTableAlias.  '.product_id IN (?)', $linkFieldIds);

        $this->addTitleToResult($select, $storeId);
        $this->addPriceToResult($select, $storeId);

        return $select;
    }

    private function addTitleToResult(Select $select, int $storeId): void
    {
        $productOptionTitleTable = $this->resource->getTableName('catalog_product_option_title');
        $connection = $this->getConnection();
        $titleExpr = $connection->getCheckSql(
            'store_option_title.title IS NULL',
            'default_option_title.title',
            'store_option_title.title'
        );

        $select->join(
            ['default_option_title' => $productOptionTitleTable],
            'default_option_title.option_id = main_table.option_id',
            ['default_title' => 'title']
        )->joinLeft(
            ['store_option_title' => $productOptionTitleTable],
            'store_option_title.option_id = main_table.option_id AND ' . $connection->quoteInto(
                'store_option_title.store_id = ?',
                $storeId
            ),
            [
                'store_title' => 'title',
                'title' => $titleExpr
            ]
        )->where(
            'default_option_title.store_id = ?',
            Store::DEFAULT_STORE_ID
        );
    }

    private function addPriceToResult(Select $select, int $storeId): void
    {
        $productOptionPriceTable = $this->resource->getTableName('catalog_product_option_price');
        $connection = $this->getConnection();
        $priceExpr = $connection->getCheckSql(
            'store_option_price.price IS NULL',
            'default_option_price.price',
            'store_option_price.price'
        );
        $priceTypeExpr = $connection->getCheckSql(
            'store_option_price.price_type IS NULL',
            'default_option_price.price_type',
            'store_option_price.price_type'
        );

        $select->joinLeft(
            ['default_option_price' => $productOptionPriceTable],
            'default_option_price.option_id = main_table.option_id AND ' . $connection->quoteInto(
                'default_option_price.store_id = ?',
                Store::DEFAULT_STORE_ID
            ),
            [
                'default_price' => 'price',
                'default_price_type' => 'price_type'
            ]
        )->joinLeft(
            ['store_option_price' => $productOptionPriceTable],
            'store_option_price.option_id = main_table.option_id AND ' . $connection->quoteInto(
                'store_option_price.store_id = ?',
                $storeId
            ),
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

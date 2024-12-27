<?php

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Category;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;

class ProductCount
{
    private ResourceConnection $resource;
    private array $categoryProductCountCache = [];

    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resource = $resourceConnection;
    }

    public function loadProductCount(array $categoryIds): array
    {
        $loadCategoryIds = $categoryIds;

        if (!empty($this->categoryProductCountCache)) {
            $loadCategoryIds = array_diff($categoryIds, array_keys($this->categoryProductCountCache));
        }

        $loadCategoryIds = array_map('intval', $loadCategoryIds);

        if (!empty($loadCategoryIds)) {
            $result = $this->getProductCount($loadCategoryIds);

            foreach ($loadCategoryIds as $categoryId) {
                $categoryId = (int)$categoryId;
                $this->categoryProductCountCache[$categoryId] = 0;

                if (isset($result[$categoryId])) {
                    $this->categoryProductCountCache[$categoryId] = (int)$result[$categoryId];
                }
            }
        }

        return $this->categoryProductCountCache;
    }

    public function getProductCount(array $categoryIds): array
    {
        $productTable = $this->resource->getTableName('catalog_category_product');

        $select = $this->getConnection()->select()->from(
            ['main_table' => $productTable],
            [
                'category_id',
                new \Zend_Db_Expr('COUNT(main_table.product_id)')
            ]
        )->where('main_table.category_id in (?)', $categoryIds);

        $select->group('main_table.category_id');

        return $this->getConnection()->fetchPairs($select);
    }

    private function getConnection(): AdapterInterface
    {
        return $this->resource->getConnection();
    }
}

<?php

declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Category;

use StreamX\ConnectorCatalog\Model\CategoryMetaData;
use Magento\Catalog\Model\Category;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Store\Model\StoreManagerInterface;

class StoreSelectModifier implements BaseSelectModifierInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
    }

    /**
     * Modify the select statement
     */
    public function execute(Select $select, int $storeId): Select
    {
        $store = $this->storeManager->getStore($storeId);
        $connection = $select->getConnection();

        $rootId = Category::TREE_ROOT_ID;
        $rootCatIdExpr = $connection->quote(sprintf("%s/%s", $rootId, $store->getRootCategoryId()));
        $catIdExpr = $connection->quote(sprintf("%s/%s/%%", $rootId, $store->getRootCategoryId()));
        $whereCondition = sprintf("path = %s OR path like %s", $rootCatIdExpr, $catIdExpr);

        $select->where($whereCondition);

        return $select;
    }
}

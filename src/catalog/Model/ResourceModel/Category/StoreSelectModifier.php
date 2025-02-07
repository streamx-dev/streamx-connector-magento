<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Category;

use Magento\Catalog\Model\Category;
use Magento\Framework\DB\Select;
use Magento\Store\Model\StoreManagerInterface;
use StreamX\ConnectorCatalog\Model\ResourceModel\SelectModifierInterface;

class StoreSelectModifier implements SelectModifierInterface
{
    private StoreManagerInterface $storeManager;

    public function __construct(StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
    }

    /**
     * Modify the select statement
     */
    public function modify(Select $select, int $storeId): void
    {
        $store = $this->storeManager->getStore($storeId);
        $connection = $select->getConnection();

        $rootId = Category::TREE_ROOT_ID;
        $rootCatIdExpr = $connection->quote(sprintf("%s/%s", $rootId, $store->getRootCategoryId()));
        $catIdExpr = $connection->quote(sprintf("%s/%s/%%", $rootId, $store->getRootCategoryId()));
        $whereCondition = sprintf("path = %s OR path like %s", $rootCatIdExpr, $catIdExpr);

        $select->where($whereCondition);
    }
}

<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Category;

use Magento\Catalog\Model\Category;
use Magento\Framework\DB\Select;
use Magento\Store\Model\StoreManagerInterface;
use StreamX\ConnectorCatalog\Model\ResourceModel\SelectModifierInterface;

class CategoryFromStoreSelectModifier implements SelectModifierInterface
{
    private StoreManagerInterface $storeManager;

    public function __construct(StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
    }

    public function modify(Select $select, int $storeId): void
    {
        $store = $this->storeManager->getStore($storeId);
        $rootCategoryPath = Category::TREE_ROOT_ID . "/" . $store->getRootCategoryId();
        $select->where("path = '$rootCategoryPath' OR path like '$rootCategoryPath/%'");
    }
}

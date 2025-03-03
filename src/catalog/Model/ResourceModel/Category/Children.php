<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Category;

use Exception;
use Magento\Framework\DB\Adapter\AdapterInterface;
use StreamX\ConnectorCatalog\Model\CategoryMetaData;
use Magento\Framework\App\ResourceConnection;
use StreamX\ConnectorCatalog\Model\ResourceModel\Category;

class Children
{
    private ResourceConnection $resource;
    private EligibleCategorySelectModifier $eligibleCategorySelectModifier;
    private CategoryMetaData $categoryMetaData;
    private Category $category;

    public function __construct(
        EligibleCategorySelectModifier $eligibleCategorySelectModifier,
        ResourceConnection $resourceModel,
        CategoryMetaData $categoryMetaData,
        Category $category
    ) {
        $this->resource = $resourceModel;
        $this->categoryMetaData = $categoryMetaData;
        $this->eligibleCategorySelectModifier = $eligibleCategorySelectModifier;
        $this->category = $category;
    }

    /**
     * @throws Exception
     */
    public function loadChildren(string $categoryPath, int $storeId): array
    {
        $select = $this->category->getCategoriesBaseSelect($storeId);

        $childIds = $this->getChildrenIds($categoryPath, $storeId);
        $select->where("entity.entity_id IN (?)", $childIds);
        $select->order('path asc');
        $select->order('position asc');

        return $this->getConnection()->fetchAll($select);
    }

    /**
     * @throws Exception
     */
    private function getChildrenIds(string $categoryPath, int $storeId): array
    {
        $connection = $this->getConnection();

        $bind = ['c_path' => "$categoryPath/%"];

        $select = $this->getConnection()->select()->from(
            ['entity' => $this->categoryMetaData->getEntityTable()],
            ['id' => 'entity_id']
        )->where(
            $connection->quoteIdentifier('path') . ' LIKE :c_path'
        );

        $this->eligibleCategorySelectModifier->modify($select, $storeId);

        return $this->getConnection()->fetchCol($select, $bind);
    }

    private function getConnection(): AdapterInterface
    {
        return $this->resource->getConnection();
    }
}

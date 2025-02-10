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
    private CompositeWithStoreChildrenModifier $selectModifier;
    private CategoryMetaData $categoryMetaData;

    public function __construct(
        CompositeWithStoreChildrenModifier $selectModifier,
        ResourceConnection $resourceModel,
        CategoryMetaData $categoryMetaData
    ) {
        $this->resource = $resourceModel;
        $this->categoryMetaData = $categoryMetaData;
        $this->selectModifier = $selectModifier;
    }

    /**
     * @throws Exception
     */
    public function loadChildren(array $category, int $storeId): array
    {
        $childIds = $this->getChildrenIds($category, $storeId);
        $select = Category::getCategoriesBaseSelect($this->resource, $this->categoryMetaData);
        $this->selectModifier->modify($select, $storeId);

        $select->where("entity.entity_id IN (?)", $childIds);
        $select->order('path asc');
        $select->order('position asc');

        return $this->getConnection()->fetchAll($select);
    }

    /**
     * @throws Exception
     */
    private function getChildrenIds(array $category, int $storeId): array
    {
        $connection = $this->getConnection();

        $bind = ['c_path' => $category['path'] . '/%'];

        $select = $this->getConnection()->select()->from(
            ['entity' => $this->getEntityTable()],
            ['id' => 'entity_id']
        )->where(
            $connection->quoteIdentifier('path') . ' LIKE :c_path'
        );

        $this->selectModifier->modify($select, $storeId);

        return $this->getConnection()->fetchCol($select, $bind);
    }

    /**
     * Retrieve category entity table
     *
     * @throws Exception
     */
    private function getEntityTable(): string
    {
        return $this->categoryMetaData->get()->getEntityTable();
    }

    private function getConnection(): AdapterInterface
    {
        return $this->resource->getConnection();
    }
}

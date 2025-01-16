<?php

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Category;

use Exception;
use Magento\Framework\DB\Adapter\AdapterInterface;
use StreamX\ConnectorCatalog\Model\CategoryMetaData;
use Magento\Framework\App\ResourceConnection;

class Children
{
    /**
     * Alias form category entity table
     */
    private const MAIN_TABLE_ALIAS = 'entity';

    private ResourceConnection $resource;
    private BaseSelectModifierInterface $baseSelectModifier;
    private CategoryMetaData $categoryMetaData;

    public function __construct(
        BaseSelectModifierInterface $baseSelectModifier,
        ResourceConnection $resourceModel,
        CategoryMetaData $categoryMetaData
    ) {
        $this->resource = $resourceModel;
        $this->categoryMetaData = $categoryMetaData;
        $this->baseSelectModifier = $baseSelectModifier;
    }

    /**
     * @throws Exception
     */
    public function loadChildren(array $category, int $storeId): array
    {
        $childIds = $this->getChildrenIds($category, $storeId);

        $select = $this->getConnection()->select()->from(
            [self::MAIN_TABLE_ALIAS => $this->getEntityTable()],
            ['parent_id']
        )->columns(['id' => 'entity_id']);

        $select = $this->baseSelectModifier->execute($select, $storeId);

        $select->where(sprintf("%s.entity_id IN (?)", self::MAIN_TABLE_ALIAS), $childIds);
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
            [self::MAIN_TABLE_ALIAS => $this->getEntityTable()],
            ['id' => 'entity_id']
        )->where(
            $connection->quoteIdentifier('path') . ' LIKE :c_path'
        );

        $select = $this->baseSelectModifier->execute($select, $storeId);

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

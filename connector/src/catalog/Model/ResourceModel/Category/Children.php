<?php

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Category;

use StreamX\ConnectorCatalog\Model\CategoryMetaData;
use StreamX\ConnectorCatalog\Model\ResourceModel\Category\BaseSelectModifierInterface;
use Magento\Framework\App\ResourceConnection;

class Children
{
    /**
     * Alias form category entity table
     */
    const MAIN_TABLE_ALIAS = 'entity';

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var int
     */
    private $isActiveAttributeId;

    /**
     * @var LoadAttributes
     */
    private $attributeDataProvider;

    /**
     * @var BaseSelectModifierInterface
     */
    private $baseSelectModifier;

    /**
     * @var CategoryMetaData
     */
    private $categoryMetaData;

    public function __construct(
        LoadAttributes $attributeDataProvider,
        BaseSelectModifierInterface $baseSelectModifier,
        ResourceConnection $resourceModel,
        CategoryMetaData $categoryMetaData
    ) {
        $this->attributeDataProvider = $attributeDataProvider;
        $this->resource = $resourceModel;
        $this->categoryMetaData = $categoryMetaData;
        $this->baseSelectModifier = $baseSelectModifier;
    }

    /**
     * @throws \Exception
     */
    public function loadChildren(array $category, int $storeId): array
    {
        $childIds = $this->getChildrenIds($category, $storeId);

        $select = $this->getConnection()->select()->from(
            [self::MAIN_TABLE_ALIAS => $this->getEntityTable()]
        );

        $select = $this->baseSelectModifier->execute($select, $storeId);

        $select->where(sprintf("%s.entity_id IN (?)", self::MAIN_TABLE_ALIAS), $childIds);
        $select->order('path asc');
        $select->order('position asc');

        return $this->getConnection()->fetchAll($select);
    }

    /**
     * @throws \Exception
     */
    private function getChildrenIds(array $category, int $storeId): array
    {
        $connection = $this->getConnection();
        $checkSql = $connection->getCheckSql('c.value_id > 0', 'c.value', 'd.value');

        $bind = ['c_path' => $category['path'] . '/%'];

        $select = $this->getConnection()->select()->from(
            [self::MAIN_TABLE_ALIAS => $this->getEntityTable()],
            'entity_id'
        )->where(
            $connection->quoteIdentifier('path') . ' LIKE :c_path'
        );

        $select = $this->baseSelectModifier->execute($select, $storeId);

        return $this->getConnection()->fetchCol($select, $bind);
    }

    /**
     * Retrieve category entity table
     *
     * @throws \Exception
     */
    private function getEntityTable(): string
    {
        return $this->categoryMetaData->get()->getEntityTable();
    }

    /**
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private function getConnection()
    {
        return $this->resource->getConnection();
    }
}

<?php

namespace StreamX\ConnectorCatalog\Model\ResourceModel;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\App\ResourceConnection;

class Attribute
{

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    public function __construct(ResourceConnection $resource, CollectionFactory $collectionFactory)
    {
        $this->resource = $resource;
        $this->collectionFactory = $collectionFactory;
    }

    public function getAttributes(array $attributeIds = [], int $fromId = 0, int $limit = 100): array
    {
        $select = $this->getAttributeCollectionSelect();
        $connection = $this->resource->getConnection();
        $sourceModelCondition = [$connection->quoteInto('source_model != ?', 'core/design_source_design')];
        $sourceModelCondition[] = 'source_model IS NULL';
        $select->where(sprintf('(%s)', implode(' OR ', $sourceModelCondition)));

        if (!empty($attributeIds)) {
            $select->where('main_table.attribute_id IN (?)', $attributeIds);
        }

        $select->where('main_table.attribute_id > ?', $fromId)
            ->limit($limit)
            ->order('main_table.attribute_id');

        return $connection->fetchAll($select);
    }

    /**
     * @return \Magento\Framework\DB\Select
     */
    private function getAttributeCollectionSelect()
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();

        return $collection->getSelect();
    }
}

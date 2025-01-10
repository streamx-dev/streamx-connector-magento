<?php

namespace StreamX\ConnectorCatalog\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

class Attribute
{
    private ResourceConnection $resource;

    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    public function getAttributes(array $attributeIds, int $fromId, int $limit): array
    {
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName('eav_attribute');
        $select = $connection->select()
            ->from($tableName, ['attribute_code', 'frontend_label'])
            ->columns(['id' => 'attribute_id']);

        $sourceModelCondition = [$connection->quoteInto('source_model != ?', 'core/design_source_design')];
        $sourceModelCondition[] = 'source_model IS NULL';
        $select->where(sprintf('(%s)', implode(' OR ', $sourceModelCondition)));

        if (!empty($attributeIds)) {
            $select->where('attribute_id IN (?)', $attributeIds);
        }

        $select->where('attribute_id > ?', $fromId)
            ->limit($limit)
            ->order('attribute_id');

        return $connection->fetchAll($select);
    }
}

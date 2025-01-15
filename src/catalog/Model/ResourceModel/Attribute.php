<?php

namespace StreamX\ConnectorCatalog\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Zend_Db_Expr;

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
        $select = $connection->select()
            ->from(
                ['a' => $this->resource->getTableName('eav_attribute')],
                ['attribute_code', 'frontend_label']
            )
            ->columns(
                ['id' => 'attribute_id']
            );

        $sourceModelCondition = [$connection->quoteInto('source_model != ?', 'core/design_source_design')];
        $sourceModelCondition[] = 'source_model IS NULL';
        $select->where(sprintf('(%s)', implode(' OR ', $sourceModelCondition)));
        $select->joinLeft(
            ['c' => $this->resource->getTableName('catalog_eav_attribute')],
            'c.attribute_id = a.attribute_id',
            ['is_filterable' => new Zend_Db_Expr('CASE WHEN is_filterable = 1 THEN true ELSE false END')]
        );

        if (!empty($attributeIds)) {
            $select->where('a.attribute_id IN (?)', $attributeIds);
        }

        $select->where('a.attribute_id > ?', $fromId)
            ->limit($limit)
            ->order('a.attribute_id');

        return $connection->fetchAll($select);
    }
}

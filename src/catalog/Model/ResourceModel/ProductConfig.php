<?php

namespace StreamX\ConnectorCatalog\Model\ResourceModel;

use StreamX\ConnectorCatalog\Model\ProductMetaData;
use Magento\Framework\App\ResourceConnection;

class ProductConfig
{

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var ProductMetaData
     */
    private $productMetaData;

    /**
     * @var
     */
    private $entityTypeId;

    public function __construct(
        ProductMetaData $productMetaData,
        ResourceConnection $resourceConnection
    ) {
        $this->resource = $resourceConnection;
        $this->productMetaData = $productMetaData;
    }

    /**
     *
     * @throws \Exception
     */
    public function getAttributesUsedForSortBy(): array
    {
        $entityType = $this->productMetaData->get()->getEavEntityType();
        $connection = $this->resource->getConnection();

        $select = $connection->select()->from(
            ['main_table' => $this->resource->getTableName('eav_attribute')],
            ['attribute_code']
        )->join(
            ['additional_table' => $this->resource->getTableName('catalog_eav_attribute')],
            'main_table.attribute_id = additional_table.attribute_id',
            []
        )->where(
            'main_table.entity_type_id = ?',
            $this->getEntityTypeId($entityType)
        )->where(
            'additional_table.used_for_sort_by = ?',
            1
        );

        return $connection->fetchCol($select);
    }

    private function getEntityTypeId(string $entityTypeCode): int
    {
        if (null === $this->entityTypeId) {
            $connection = $this->resource->getConnection();
            $select = $connection->select()->from(
                $this->resource->getTableName('eav_entity_type'),
                ['entity_type_id']
            );

            $select->where('entity_type_code = ?', $entityTypeCode);

            $this->entityTypeId = $connection->fetchOne($select);
        }

        return (int) $this->entityTypeId;
    }
}

<?php

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Attribute;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use StreamX\ConnectorCatalog\Model\Attributes\AttributeDefinition;
use StreamX\ConnectorCatalog\Model\Attributes\AttributeOptionDefinition;
use StreamX\ConnectorCatalog\Model\Attribute\LoadOptions;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\SpecialAttributes;
use Zend_Db_Expr;

class LoadAttributes
{
    private ResourceConnection $resource;
    private LoadOptions $loadOptions;

    public function __construct(
        ResourceConnection $resource,
        LoadOptions $loadOptions
    ) {
        $this->resource = $resource;
        $this->loadOptions = $loadOptions;
    }

    /**
     * @param int[] $attributeIds
     * @param int $storeId
     * @param int $fromId loads only attributes with ID greater than $fromId
     * @param int|null $limit loads all matching rows up to $limit rows, or loads all matching rows if $limit is null
     * @return AttributeDefinition[]
     */
    public function loadAttributeDefinitionsByIds(array $attributeIds, int $storeId, int $fromId = 0, ?int $limit = null): array
    {
        return $this->loadAttributeDefinitions($storeId, 'attribute_id', $attributeIds, $fromId, $limit);
    }

    /**
     * @param string[] $attributeCodes
     * @param int $storeId
     * @param int $fromId loads only attributes with ID greater than $fromId
     * @param int|null $limit loads all matching rows up to $limit rows, or loads all matching rows if $limit is null
     * @return AttributeDefinition[]
     */
    public function loadAttributeDefinitionsByCodes(array $attributeCodes, int $storeId, int $fromId = 0, ?int $limit = null): array
    {
        return $this->loadAttributeDefinitions($storeId, 'attribute_code', $attributeCodes, $fromId, $limit);
    }

    /**
     * @return AttributeDefinition[]
    */
    private function loadAttributeDefinitions(int $storeId, string $columnToFilterBy, array $columnValues, int $fromId, ?int $limit): array
    {
        $connection = $this->resource->getConnection();
        $select = $this->createAttributesSelect($connection, $fromId, $limit);
        $select->where("ea.$columnToFilterBy IN (?)", $columnValues);
        $attributeRows = $connection->fetchAll($select);

        foreach ($attributeRows as &$attributeRow) {
            $attributeCode = $attributeRow['attribute_code'];
            $attributeRow['options'] = $this->getOptionsArray($attributeRow, $attributeCode, $storeId);
        }

        return $this->mapAttributeRowsToDtos($attributeRows);
    }

    private function createAttributesSelect(AdapterInterface $connection, int $fromId, ?int $limit): Select
    {
        return $connection->select()
            ->from(
                ['ea' => $this->resource->getTableName('eav_attribute')],
                ['attribute_code', 'frontend_input', 'source_model']
            )->columns([
                'id' => 'attribute_id',
                'frontend_label' => $connection->getIfNullSql('frontend_label', "''")
            ])
            ->joinLeft(
                ['cea' => $this->resource->getTableName('catalog_eav_attribute')],
                'cea.attribute_id = ea.attribute_id',
                ['is_facet' => new Zend_Db_Expr('is_filterable = 1')]
            )
            ->where("ea.attribute_id > $fromId")
            ->limit($limit)
            ->order('ea.attribute_id');
    }

    private function getOptionsArray(array $attributeRow, string $attributeCode, int $storeId): array
    {
        if ($this->useSource($attributeRow)) {
            if (SpecialAttributes::isSpecialAttribute($attributeCode)) {
                return SpecialAttributes::getOptionsArray($attributeCode);
            } else {
                return $this->loadOptions->execute($attributeCode, $storeId);
            }
        } else {
            return [];
        }
    }

    private function useSource(array $attributeRow): bool
    {
        return $attributeRow['frontend_input'] === 'select'
            || $attributeRow['frontend_input'] === 'multiselect'
            || $attributeRow['source_model'] != '';
    }

    /**
     * @return AttributeDefinition[]
     */
    private function mapAttributeRowsToDtos(array $attributeRows): array
    {
        return array_map(
            function (array $attributeRow) {
                return new AttributeDefinition(
                    $attributeRow['id'],
                    $attributeRow['attribute_code'],
                    $attributeRow['frontend_label'],
                    $attributeRow['is_facet'] ?? false,
                    $this->mapAttributeOptionRowsToDtos($attributeRow['options'])
                );
            },
            $attributeRows
        );
    }

    /**
     * @return AttributeOptionDefinition[]
     */
    private function mapAttributeOptionRowsToDtos(array $optionRows): array
    {
        return array_map(
            function ($option) {
                return new AttributeOptionDefinition(
                    $option['value'],
                    $option['label'],
                );
            },
            $optionRows
        );
    }
}

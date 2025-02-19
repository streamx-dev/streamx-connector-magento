<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Product;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use StreamX\ConnectorCatalog\Model\Attributes\AttributeDefinition;
use StreamX\ConnectorCatalog\Model\Attributes\AttributeOptionDefinition;
use StreamX\ConnectorCatalog\Model\Attribute\LoadOptions;
use StreamX\ConnectorCatalog\Model\Attributes\AttributeOptionSwatchDefinition;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\SpecialAttributes;
use StreamX\ConnectorCatalog\Model\ResourceModel\ProductConfig;
use Zend_Db_Expr;

class LoadAttributeDefinitions
{
    private ResourceConnection $resource;
    private LoadOptions $loadOptions;
    private ProductConfig $productConfig;

    public function __construct(
        ResourceConnection $resource,
        LoadOptions $loadOptions,
        ProductConfig $productConfig
    ) {
        $this->resource = $resource;
        $this->loadOptions = $loadOptions;
        $this->productConfig = $productConfig;
    }

    /**
     * @param int[] $attributeIds loads all attributes if $attributeIds is empty, otherwise loads only these attributes
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
     * @param string[] $attributeCodes loads all attributes if $attributeCodes is empty, otherwise loads only these attributes
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
        if (!empty($columnValues)) {
            $select->where("ea.$columnToFilterBy IN (?)", $columnValues);
        }
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
                ['is_facet' => new Zend_Db_Expr('CASE WHEN is_filterable = 1 THEN true ELSE false END')]
            )
            ->where("ea.attribute_id > $fromId")
            ->where('ea.entity_type_id = ?', $this->productConfig->getEntityTypeId())
            ->limit($limit)
            ->order('ea.attribute_id');
    }

    private function getOptionsArray(array $attributeRow, string $attributeCode, int $storeId): array
    {
        if ($this->useSource($attributeRow)) {
            if (SpecialAttributes::isSpecialAttribute($attributeCode)) {
                return SpecialAttributes::getOptionsArray($attributeCode);
            } else {
                return $this->loadOptions->getOptionsArray($attributeCode, $storeId);
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
                    (int) $attributeRow['id'],
                    $attributeRow['attribute_code'],
                    $attributeRow['frontend_label'],
                    (bool) $attributeRow['is_facet'],
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
                    (int) $option['id'],
                    (string) $option['value'],
                    $this->mapAttributeOptionRowToSwatchDto($option)
                );
            },
            $optionRows
        );
    }

    private function mapAttributeOptionRowToSwatchDto(array $optionRow): ?AttributeOptionSwatchDefinition
    {
        if (isset($optionRow['swatch'])) {
            return new AttributeOptionSwatchDefinition(
                $optionRow['swatch']['type_string'],
                $optionRow['swatch']['value']
            );
        }

        return null;
    }
}

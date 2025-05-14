<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Product;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use StreamX\ConnectorCatalog\Model\Attributes\AttributeDefinition;
use StreamX\ConnectorCatalog\Model\Attributes\AttributeOptionDefinition;
use StreamX\ConnectorCatalog\Model\Attribute\LoadOptions;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\SpecialAttributes;
use StreamX\ConnectorCatalog\Model\ProductMetaData;
use Zend_Db_Expr;

class LoadAttributeDefinitions
{
    private ResourceConnection $resource;
    private LoadOptions $loadOptions;
    private ProductMetaData $productMetaData;

    public function __construct(
        ResourceConnection $resource,
        LoadOptions $loadOptions,
        ProductMetaData $productMetaData
    ) {
        $this->resource = $resource;
        $this->loadOptions = $loadOptions;
        $this->productMetaData = $productMetaData;
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
        $select = $this->createAttributesSelect($connection, $storeId, $fromId, $limit);
        if (!empty($columnValues)) {
            $select->where("ea.$columnToFilterBy IN (?)", $columnValues);
        }

        $attributeRows = $connection->fetchAll($select);
        return $this->mapAttributeRowsToDtosWithOptions($attributeRows, $storeId);
    }

    private function createAttributesSelect(AdapterInterface $connection, int $storeId, int $fromId, ?int $limit): Select
    {
        return $connection->select()
            ->from(
                ['ea' => $this->resource->getTableName('eav_attribute')],
                ['attribute_code', 'frontend_input', 'source_model']
            )->columns([
                'id' => 'attribute_id',
                'default_frontend_label' => 'frontend_label'
            ])
            ->joinLeft(
                ['cea' => $this->resource->getTableName('catalog_eav_attribute')],
                'cea.attribute_id = ea.attribute_id',
                ['is_facet' => new Zend_Db_Expr('CASE WHEN is_filterable = 1 THEN true ELSE false END')]
            )
            ->joinLeft(
                ['eal' => $this->resource->getTableName('eav_attribute_label')],
                "eal.attribute_id = ea.attribute_id AND eal.store_id = $storeId",
                ['store_level_frontend_label' => 'value']
            )
            ->where("ea.attribute_id > $fromId")
            ->where('ea.entity_type_id = ?', $this->productMetaData->getEntityTypeId())
            ->limit($limit)
            ->order('ea.attribute_id');
    }

    /**
     * @return AttributeOptionDefinition[]
     */
    private function getOptions(array $attributeRow, string $attributeCode, int $storeId): array
    {
        if ($this->useSource($attributeRow)) {
            if (SpecialAttributes::isSpecialAttribute($attributeCode)) {
                return SpecialAttributes::getOptions($attributeCode);
            } else {
                return $this->loadOptions->getOptions($attributeCode, $storeId);
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
    private function mapAttributeRowsToDtosWithOptions(array $attributeRows, int $storeId): array
    {
        $attributeDtos = [];

        foreach ($attributeRows as $attributeRow) {
            $attributeCode = $attributeRow['attribute_code'];
            $label = $attributeRow['store_level_frontend_label'] ?? $attributeRow['default_frontend_label'] ?? '';
            $options = $this->getOptions($attributeRow, $attributeCode, $storeId);

            $attributeDtos[] = new AttributeDefinition(
                (int)$attributeRow['id'],
                $attributeCode,
                $label,
                (bool)$attributeRow['is_facet'],
                $options
            );
        }

        return $attributeDtos;
    }
}

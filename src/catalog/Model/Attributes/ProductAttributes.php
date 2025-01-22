<?php

namespace StreamX\ConnectorCatalog\Model\Attributes;

use Magento\Framework\App\ResourceConnection;
use StreamX\ConnectorCatalog\Model\SystemConfig\CatalogConfig;
use StreamX\ConnectorCatalog\Model\Attribute\LoadOptions;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\SpecialAttributes;
use Zend_Db_Expr;

class ProductAttributes
{
    const REQUIRED_ATTRIBUTES = [
        'sku',
        'url_path',
        'url_key',
        'name',
        'price',
        'visibility',
        'status',
        'price_type',
    ];

    private CatalogConfig $catalogConfig;
    private ResourceConnection $resource;
    private LoadOptions $loadOptions;

    public function __construct(
        CatalogConfig $catalogConfiguration,
        ResourceConnection $resource,
        LoadOptions $loadOptions
    ) {
        $this->catalogConfig = $catalogConfiguration;
        $this->resource = $resource;
        $this->loadOptions = $loadOptions;
    }

    /**
     * @param int $storeId
     * @return AttributeDefinition[]
     */
    public function getAttributesToIndex(int $storeId): array
    {
        $attributeCodes = $this->catalogConfig->getAttributesToIndex($storeId);

        return empty($attributeCodes)
            ? []
            : array_merge($attributeCodes, self::REQUIRED_ATTRIBUTES);
    }

    /**
     * @param string[] $attributeCodes
     * @param int $storeId
     * @return AttributeDefinition[]
     */
    public function loadAttributeDefinitions(array $attributeCodes, int $storeId): array
    {
        $attributeRows = $this->selectAttributesFromDb($attributeCodes);

        foreach ($attributeRows as &$attributeRow) {
            $attributeCode = $attributeRow['attribute_code'];
            $attributeRow['options'] = $this->getOptionsArray($attributeRow, $attributeCode, $storeId);
        }

        return $this->mapAttributeRowsToDtos($attributeRows);
    }

    private function selectAttributesFromDb(array $attributeCodes): array
    {
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName('eav_attribute');
        $select = $connection->select()
            ->from(['ea' => $tableName], ['attribute_code', 'frontend_input', 'source_model'])
            ->columns(['frontend_label' => $connection->getIfNullSql('frontend_label', "''")]) // TODO should frontend label from getStoreLabelsByAttributeId take precedence over frontend_label?
            ->joinLeft(
                ['cea' => $this->resource->getTableName('catalog_eav_attribute')],
                'cea.attribute_id = ea.attribute_id',
                ['is_filterable' => new Zend_Db_Expr('CASE WHEN is_filterable = 1 THEN true ELSE false END')]
            )
            ->where('ea.attribute_code IN (?)', $attributeCodes);

        return $connection->fetchAll($select);
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
                    $attributeRow['attribute_code'],
                    $attributeRow['frontend_label'],
                    $attributeRow['is_filterable'],
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

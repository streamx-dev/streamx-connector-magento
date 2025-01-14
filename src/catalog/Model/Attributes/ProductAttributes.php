<?php

namespace StreamX\ConnectorCatalog\Model\Attributes;

use Magento\Framework\App\ResourceConnection;
use StreamX\ConnectorCatalog\Api\CatalogConfigurationInterface;
use StreamX\ConnectorCatalog\Model\Attribute\LoadOptions;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\SpecialAttributes;

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

    private CatalogConfigurationInterface $catalogConfig;
    private ResourceConnection $resource;
    private LoadOptions $loadOptions;

    public function __construct(
        CatalogConfigurationInterface $catalogConfiguration,
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
        $attributeCodes = $this->catalogConfig->getAllowedAttributesToIndex($storeId);

        if (empty($attributeCodes)) {
            return [];
        }

        $attributeCodes = array_merge($attributeCodes, self::REQUIRED_ATTRIBUTES);
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
            ->from($tableName, ['attribute_code', 'frontend_input', 'source_model'])
            ->columns(['frontend_label' => $connection->getIfNullSql('frontend_label', "''")]) // TODO should frontend label from getStoreLabelsByAttributeId take precedence over frontend_label?
            ->where('attribute_code IN (?)', $attributeCodes);

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

<?php

namespace StreamX\ConnectorCatalog\Model\Attributes;

use Magento\Framework\App\ResourceConnection;
use StreamX\ConnectorCatalog\Api\CatalogConfigurationInterface;
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

    private CatalogConfigurationInterface $catalogConfig;
    private ResourceConnection $resource;

    public function __construct(
        CatalogConfigurationInterface $catalogConfiguration,
        ResourceConnection $resource)
    {
        $this->catalogConfig = $catalogConfiguration;
        $this->resource = $resource;
    }

    /**
     * @param int $storeId
     * @return AttributeDefinitionDto[]
     */
    public function getAttributesToIndex(int $storeId): array
    {
        $attributeCodes = $this->catalogConfig->getAllowedAttributesToIndex($storeId);

        if (empty($attributeCodes)) {
            return [];
        }

        $attributeCodes = array_merge($attributeCodes, self::REQUIRED_ATTRIBUTES);
        $attributeRows = $this->selectAttributesFromDb($attributeCodes);
        return $this->mapAttributeRowsToDtos($attributeRows);
    }

    private function selectAttributesFromDb(array $attributeCodes): array
    {
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName('eav_attribute');
        $select = $connection->select()
            ->from($tableName, ['attribute_code'])
            ->columns(['frontend_label' => $connection->getIfNullSql('frontend_label', "''")]) // TODO should frontend label from getStoreLabelsByAttributeId take precedence over frontend_label?
            ->where('attribute_code IN (?)', $attributeCodes);

        return $connection->fetchAll($select);
    }

    /**
     * @return AttributeDefinitionDto[]
     */
    public function mapAttributeRowsToDtos(array $attributeRows): array
    {
        return array_map(
            function (array $attributeRow) {
                return new AttributeDefinitionDto(
                    $attributeRow['attribute_code'],
                    $attributeRow['frontend_label']
                );
            },
            $attributeRows
        );
    }
}

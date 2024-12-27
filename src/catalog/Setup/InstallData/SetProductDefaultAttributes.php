<?php

namespace StreamX\ConnectorCatalog\Setup\InstallData;

use Magento\Config\Model\ResourceModel\Config;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use StreamX\ConnectorCatalog\Api\CatalogConfigurationInterface;

class SetProductDefaultAttributes
{
    private const ENTITY_TYPE = 'catalog_product';

    private const DEFAULT_EXPORTED_ATTRIBUTES = [
        'description',
        'image',
        'small_image',
        'thumbnail',
        'media_gallery',
        'meta_title',
        'meta_description',
        'special_price',
        'special_from_date',
        'special_to_date',
        'tax_class_id'
    ];

    private const DEFAULT_EXPORTED_CHILD_ATTRIBUTES = [
        'image',
        'small_image',
        'thumbnail',
        'media_gallery',
        'special_price',
        'special_from_date',
        'special_to_date'
    ];

    private Config $resourceConfig;
    private EavConfig $eavConfig;

    public function __construct(Config $resourceConfig, EavConfig $eavConfig) {
        $this->resourceConfig = $resourceConfig;
        $this->eavConfig = $eavConfig;
    }

    public function execute(): void
    {
        $this->saveConfig(
            self::DEFAULT_EXPORTED_ATTRIBUTES,
            CatalogConfigurationInterface::PRODUCT_ATTRIBUTES
        );

        $this->saveConfig(
            self::DEFAULT_EXPORTED_CHILD_ATTRIBUTES,
            CatalogConfigurationInterface::CHILD_ATTRIBUTES
        );
    }

    private function saveConfig(array $attributeCodes, string $configFieldName): void
    {
        $attributeIds = $this->getAttributeIdsByCodes($attributeCodes);

        if (!empty($attributeIds)) {
            $configPath = CatalogConfigurationInterface::CATALOG_SETTINGS_XML_PREFIX . '/' . $configFieldName;
            $configValue = implode(',', $attributeIds);
            $this->resourceConfig->saveConfig($configPath, $configValue);
        }
    }

    private function getAttributeIdsByCodes(array $attributes): array
    {
        $attributeIds = [];

        foreach ($attributes as $attributeCode) {
            $attribute = $this->eavConfig->getAttribute(self::ENTITY_TYPE, $attributeCode);

            if ($attribute->getId()) {
                $attributeIds[] = $attribute->getId();
            }
        }

        return $attributeIds;
    }

}

<?php declare(strict_types=1);

namespace Divante\VsbridgeIndexerCatalog\Model\SystemConfig;

use Divante\VsbridgeIndexerCatalog\Api\CatalogConfigurationInterface;
use Divante\VsbridgeIndexerCatalog\Model\Product\GetAttributeCodesByIds;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class CatalogConfig implements CatalogConfigurationInterface
{
    /**
     * @var array
     */
    private $settings = [];

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var GetAttributeCodesByIds
     */
    private $getAttributeCodesByIds;

    /**
     * Settings constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param GetAttributeCodesByIds $getAttributeCodesByIds
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        GetAttributeCodesByIds $getAttributeCodesByIds
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->getAttributeCodesByIds = $getAttributeCodesByIds;
    }

    public function useMagentoUrlKeys(): bool
    {
        return (bool) $this->getConfigParam(CatalogConfigurationInterface::USE_MAGENTO_URL_KEYS);
    }

    public function useUrlKeyToGenerateSlug(): bool
    {
        return (bool) $this->getConfigParam(CatalogConfigurationInterface::USE_URL_KEY_TO_GENERATE_SLUG);
    }

    public function useCatalogRules(): bool
    {
        return (bool) $this->getConfigParam(CatalogConfigurationInterface::USE_CATALOG_RULES);
    }

    public function syncTierPrices(): bool
    {
        return (bool) $this->getConfigParam(CatalogConfigurationInterface::SYNC_TIER_PRICES);
    }

    public function addParentSku(): bool
    {
        return (bool) $this->getConfigParam(CatalogConfigurationInterface::ADD_PARENT_SKU);
    }

    public function addSwatchesToConfigurableOptions(): bool
    {
        return (bool) $this->getConfigParam(CatalogConfigurationInterface::ADD_SWATCHES_OPTIONS);
    }

    public function canExportAttributesMetadata(): bool
    {
        return (bool) $this->getConfigParam(CatalogConfigurationInterface::EXPORT_ATTRIBUTES_METADATA);
    }

    public function getAllowedProductTypes(int $storeId): array
    {
        $types = $this->getConfigParam(CatalogConfigurationInterface::ALLOWED_PRODUCT_TYPES, $storeId);

        if (null === $types || '' === $types) {
            $types = [];
        } else {
            $types = explode(',', $types);
        }

        return $types;
    }

    public function getAllowedAttributesToIndex(int $storeId): array
    {
        $attributes = (string)$this->getConfigParam(
            CatalogConfigurationInterface::PRODUCT_ATTRIBUTES,
            $storeId
        );

        return $this->getAttributeCodesByIds->execute($attributes);
    }

    public function getAllowedChildAttributesToIndex(int $storeId): array
    {
        $attributes = (string)$this->getConfigParam(
            CatalogConfigurationInterface::CHILD_ATTRIBUTES,
            $storeId
        );

        return $this->getAttributeCodesByIds->execute($attributes);
    }

    public function getConfigurableChildrenBatchSize(int $storeId): int
    {
        return (int) $this->getConfigParam(
            CatalogConfigurationInterface::CONFIGURABLE_CHILDREN_BATCH_SIZE,
            $storeId
        );
    }

    /**
     * Retrieve config value by path and scope.
     *
     * @param string $configField
     * @param int|null $storeId
     *
     * @return string|null
     */
    private function getConfigParam(string $configField, $storeId = null)
    {
        $key = $configField . (string) $storeId;

        if (!isset($this->settings[$key])) {
            $path = CatalogConfigurationInterface::CATALOG_SETTINGS_XML_PREFIX . '/' . $configField;
            $scopeType = ($storeId) ? ScopeInterface::SCOPE_STORES : ScopeConfigInterface::SCOPE_TYPE_DEFAULT;

            $configValue = $this->scopeConfig->getValue($path, $scopeType, $storeId);
            $this->settings[$key] = $configValue;
        }

        return $this->settings[$key];
    }
}

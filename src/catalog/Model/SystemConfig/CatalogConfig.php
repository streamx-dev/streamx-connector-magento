<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\SystemConfig;

use StreamX\ConnectorCatalog\Api\CatalogConfigurationInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class CatalogConfig implements CatalogConfigurationInterface
{
    private array $settings = [];
    private ScopeConfigInterface $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
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

    public function getConfigurableChildrenBatchSize(int $storeId): int
    {
        return (int) $this->getConfigParam(
            CatalogConfigurationInterface::CONFIGURABLE_CHILDREN_BATCH_SIZE,
            $storeId
        );
    }

    /**
     * Retrieve config value by path and scope.
     */
    private function getConfigParam(string $configField, int $storeId = null): ?string
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

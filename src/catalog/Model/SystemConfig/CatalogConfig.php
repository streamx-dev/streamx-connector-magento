<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\SystemConfig;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class CatalogConfig
{
    private const CATALOG_SETTINGS_XML_PREFIX = 'streamx_connector_settings/catalog_settings';

    /**
     * Slug/url key config
     */
    private const USE_URL_KEY_TO_GENERATE_SLUG = 'use_url_key_to_generate_slug';
    private const USE_URL_KEY_AND_ID_TO_GENERATE_SLUG = 'use_url_key_and_id_to_generate_slug';

    /**
     * Prices
     */
    private const USE_CATALOG_RULES = 'use_catalog_rules';
    private const SYNC_TIER_PRICES = 'sync_tier_prices';

    /**
     * Allow product types to reindex
     */
    private const ALLOWED_PRODUCT_TYPES = 'allowed_product_types';

    /**
     * Product attributes to reindex
     */
    private const PRODUCT_ATTRIBUTES = 'product_attributes';
    private const CHILD_ATTRIBUTES = 'child_attributes';

    private array $settings = [];
    private ScopeConfigInterface $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig) {
        $this->scopeConfig = $scopeConfig;
    }

    public function useUrlKeyToGenerateSlug(): bool
    {
        return (bool) $this->getConfigParam(self::USE_URL_KEY_TO_GENERATE_SLUG);
    }

    public function useUrlKeyAndIdToGenerateSlug(): bool
    {
        return (bool) $this->getConfigParam(self::USE_URL_KEY_AND_ID_TO_GENERATE_SLUG);
    }

    public function useCatalogRules(): bool
    {
        return (bool) $this->getConfigParam(self::USE_CATALOG_RULES);
    }

    public function syncTierPrices(): bool
    {
        return (bool) $this->getConfigParam(self::SYNC_TIER_PRICES);
    }

    public function getAllowedProductTypes(int $storeId): array
    {
        $types = $this->getConfigParam(self::ALLOWED_PRODUCT_TYPES, $storeId);

        if (null === $types || '' === $types) {
            $types = [];
        } else {
            $types = explode(',', $types);
        }

        return $types;
    }

    // TODO: make sure attributes required by Unified Data Model are not configurable, and will be indexed always
    public function getAttributesToIndex(int $storeId): array
    {
        return $this->explodeAttributeCodes(self::PRODUCT_ATTRIBUTES, $storeId);
    }

    // TODO: make sure attributes required by Unified Data Model are not configurable, and will be indexed always
    public function getChildAttributesToIndex(int $storeId): array
    {
        return $this->explodeAttributeCodes(self::CHILD_ATTRIBUTES, $storeId);
    }

    private function explodeAttributeCodes(string $configParamName, int $storeId): array
    {
        $configParam = $this->getConfigParam($configParamName, $storeId);
        return preg_split('/,/', $configParam, -1, PREG_SPLIT_NO_EMPTY); // split removing empty entries
    }

    /**
     * Retrieve config value by path and scope.
     */
    private function getConfigParam(string $configField, int $storeId = null): ?string
    {
        $key = $configField . (string) $storeId;

        if (!isset($this->settings[$key])) {
            $path = self::CATALOG_SETTINGS_XML_PREFIX . '/' . $configField;
            $scopeType = ($storeId) ? ScopeInterface::SCOPE_STORES : ScopeConfigInterface::SCOPE_TYPE_DEFAULT;

            $configValue = $this->scopeConfig->getValue($path, $scopeType, $storeId);
            $this->settings[$key] = $configValue;
        }

        return $this->settings[$key];
    }
}

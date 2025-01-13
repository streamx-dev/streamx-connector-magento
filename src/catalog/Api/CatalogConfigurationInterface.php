<?php

namespace StreamX\ConnectorCatalog\Api;

interface CatalogConfigurationInterface
{
    const CATALOG_SETTINGS_XML_PREFIX = 'streamx_connector_settings/catalog_settings';

    /**
     * Slug/url key config
     */
    const USE_MAGENTO_URL_KEYS = 'use_magento_url_keys';
    const USE_URL_KEY_TO_GENERATE_SLUG = 'use_url_key_to_generate_slug';

    /**
     * Prices
     */
    const USE_CATALOG_RULES = 'use_catalog_rules';
    const SYNC_TIER_PRICES = 'sync_tier_prices';

    const ADD_SWATCHES_OPTIONS = 'add_swatches_to_configurable_options';

    /**
     * Allow product types to reindex
     */
    const ALLOWED_PRODUCT_TYPES = 'allowed_product_types';

    /**
     * Product attributes to reindex
     */
    const PRODUCT_ATTRIBUTES = 'product_attributes';
    const CHILD_ATTRIBUTES = 'child_attributes';

    /**
     * @const string
     */
    const CONFIGURABLE_CHILDREN_BATCH_SIZE = 'configurable_children_batch_size';

    public function useMagentoUrlKeys(): bool;

    public function useUrlKeyToGenerateSlug(): bool;

    public function useCatalogRules(): bool;

    public function syncTierPrices(): bool;

    public function addSwatchesToConfigurableOptions();

    public function getAllowedProductTypes(int $storeId): array;

    // TODO: make sure attributes required by Unified Data Model are not configurable, and will be indexed always
    public function getAllowedAttributesToIndex(int $storeId): array;

    public function getAllowedChildAttributesToIndex(int $storeId): array;

    public function getConfigurableChildrenBatchSize(int $storeId): int;
}

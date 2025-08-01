<?php

namespace StreamX\ConnectorCatalog\test\integration\utils;

class ConfigurationKeyPaths {
    public const PRODUCT_ATTRIBUTES = 'streamx_connector_settings/catalog_settings/product_attributes';
    public const EXPORT_PRODUCTS_NOT_VISIBLE_INDIVIDUALLY = 'streamx_connector_settings/catalog_settings/export_products_not_visible_individually';
    public const USE_PRICES_INDEX = 'streamx_connector_settings/catalog_settings/use_prices_index';
    public const USE_CATALOG_PRICE_RULES = 'streamx_connector_settings/catalog_settings/use_catalog_price_rules';
    public const SLUG_GENERATION_STRATEGY = 'streamx_connector_settings/catalog_settings/slug_generation_strategy';
    public const ALLOWED_PRODUCT_TYPES = 'streamx_connector_settings/catalog_settings/allowed_product_types';

    public const INGESTION_BASE_URL = 'streamx_connector_settings/streamx_client/ingestion_base_url';

    public const ENABLE_RABBIT_MQ = 'streamx_connector_settings/rabbit_mq/enable';

    public const BATCH_INDEXING_SIZE = 'streamx_connector_settings/general_settings/batch_indexing_size';

    public const BASE_SECURE_LINK_URL = 'web/secure/base_link_url';
}
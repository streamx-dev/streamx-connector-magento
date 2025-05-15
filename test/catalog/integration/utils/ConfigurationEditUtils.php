<?php

namespace StreamX\ConnectorCatalog\test\integration\utils;

class ConfigurationKeyPaths {
    public const PRODUCT_ATTRIBUTES = 'streamx_connector_settings/catalog_settings/product_attributes';
    public const EXPORT_PRODUCTS_NOT_VISIBLE_INDIVIDUALLY = 'streamx_connector_settings/catalog_settings/export_products_not_visible_individually';
    public const USE_PRICES_INDEX = 'streamx_connector_settings/catalog_settings/use_prices_index';
    public const USE_CATALOG_PRICE_RULES = 'streamx_connector_settings/catalog_settings/use_catalog_price_rules';
    public const SLUG_GENERATION_STRATEGY = 'streamx_connector_settings/catalog_settings/slug_generation_strategy';
    public const ALLOWED_PRODUCT_TYPES = 'streamx_connector_settings/catalog_settings/allowed_product_types';
    public const ENABLE_RABBIT_MQ = 'streamx_connector_settings/rabbit_mq/enable';
}

class ConfigurationEditUtils {

    private const CONFIGURATION_EDIT_ENDPOINT = 'configuration/edit';

    private function __construct() {
        // no instances
    }

    public static function setConfigurationValue(string $path, string $value): void {
        ConfigurationEditUtils::callMagentoConfigurationEditEndpoint($path, $value);
    }

    public static function restoreConfigurationValue(string $path): void {
        self::setConfigurationValue($path, ConfigurationEditUtils::readDefaultValue($path));
    }

    public static function setIndexedProductAttributes(string ...$attributeCodes): void {
        self::setConfigurationValue(ConfigurationKeyPaths::PRODUCT_ATTRIBUTES, implode(',', $attributeCodes));
    }

    public static function addIndexedProductAttributes(string ...$attributeCodes): void {
        $attributes = explode(',', self::readDefaultValue(ConfigurationKeyPaths::PRODUCT_ATTRIBUTES));
        array_push($attributes, ...$attributeCodes);
        $attributes = array_unique($attributes);
        self::setIndexedProductAttributes(...$attributes);
    }

    public static function unsetIndexedProductAttribute(string $attributeCode): void {
        $attributes = explode(',', self::readDefaultValue(ConfigurationKeyPaths::PRODUCT_ATTRIBUTES));
        $attributes = array_filter($attributes, fn($val) => $val !== $attributeCode);
        self::setIndexedProductAttributes(...$attributes);
    }

    public static function allowIndexingAllProductAttributes(): void {
        self::setConfigurationValue(ConfigurationKeyPaths::PRODUCT_ATTRIBUTES, '');
    }

    public static function restoreDefaultIndexedProductAttributes(): void {
        self::restoreConfigurationValue(ConfigurationKeyPaths::PRODUCT_ATTRIBUTES);
    }

    private static function readDefaultValue(string $configurationFieldPath): string {
        $defaultValuesFileContent = FileUtils::readSourceFileContent(
            str_contains($configurationFieldPath, 'catalog_settings')
                ? 'src/catalog/etc/config.xml'
                : 'src/core/etc/config.xml'
        );
        $xmlDoc = simplexml_load_string($defaultValuesFileContent);
        return (string)$xmlDoc->xpath("//$configurationFieldPath")[0];
    }

    private static function callMagentoConfigurationEditEndpoint(string $configurationFieldPath, string $value): void {
        MagentoEndpointsCaller::call(self::CONFIGURATION_EDIT_ENDPOINT, [
            'configurationFieldPath' => $configurationFieldPath,
            'value' => $value
        ]);
    }
}
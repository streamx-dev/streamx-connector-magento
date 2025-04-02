<?php

namespace StreamX\ConnectorCatalog\test\integration\utils;

class ConfigurationKeyPaths {
    public const PRODUCT_ATTRIBUTES = 'streamx_connector_settings/catalog_settings/product_attributes';
    public const EXPORT_PRODUCTS_NOT_VISIBLE_INDIVIDUALLY = 'streamx_connector_settings/catalog_settings/export_products_not_visible_individually';
    public const USE_PRICES_INDEX = 'streamx_connector_settings/catalog_settings/use_prices_index';
    public const USE_CATALOG_PRICE_RULES = 'streamx_connector_settings/catalog_settings/use_catalog_price_rules';
    public const USE_URL_KEY_AND_ID_TO_GENERATE_SLUG ='streamx_connector_settings/catalog_settings/use_url_key_and_id_to_generate_slug';
}

class ConfigurationEditUtils {

    private const CONFIGURATION_EDIT_ENDPOINT = 'configuration/edit';

    private function __construct() {
        // no instances
    }

    public static function setConfigurationValue(string $path, string $value): void {
        self::setConfigurationValues([$path => $value]);
    }

    public static function setConfigurationValues(array $pathValueMap): void {
        foreach ($pathValueMap as $path => $value) {
            self::callMagentoConfigurationEditEndpoint($path, $value);
        }
        MagentoOperationsExecutor::flushConfigCache();
    }

    public static function restoreConfigurationValue(string $path): void {
        self::restoreConfigurationValues([$path]);
    }

    public static function restoreConfigurationValues(array $paths): void {
        foreach ($paths as $path) {
            self::callMagentoConfigurationEditEndpoint($path, self::readDefaultValue($path));
        }
        MagentoOperationsExecutor::flushConfigCache();
    }

    public static function setIndexedProductAttributes(string ...$attributeCodes): void {
        self::setConfigurationValue(ConfigurationKeyPaths::PRODUCT_ATTRIBUTES, implode(',', $attributeCodes));
    }

    public static function addIndexedProductAttributes(string ...$attributeCodes): void {
        $attributes = explode(',', self::readDefaultValue(ConfigurationKeyPaths::PRODUCT_ATTRIBUTES));
        array_push($attributes, ...$attributeCodes);
        $attributes = array_unique($attributes);
        self::setConfigurationValue(ConfigurationKeyPaths::PRODUCT_ATTRIBUTES, implode(',', $attributes));
    }

    public static function allowIndexingAllProductAttributes(): void {
        self::setConfigurationValue(ConfigurationKeyPaths::PRODUCT_ATTRIBUTES, '');
    }

    public static function restoreDefaultIndexedProductAttributes(): void {
        self::restoreConfigurationValue(ConfigurationKeyPaths::PRODUCT_ATTRIBUTES);
    }

    private static function readDefaultValue(string $configurationFieldPath): string {
        $defaultValuesFileContent = FileUtils::readSourceFileContent('src/catalog/etc/config.xml');
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
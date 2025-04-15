<?php

namespace StreamX\ConnectorCatalog\test\integration\utils;

class ConfigurationEditUtils {

    private const CONFIGURATION_EDIT_ENDPOINT = 'configuration/edit';
    private const PRODUCT_ATTRIBUTES_PATH = 'streamx_connector_settings/catalog_settings/product_attributes';
    public const EXPORT_PRODUCTS_NOT_VISIBLE_INDIVIDUALLY_PATH = 'streamx_connector_settings/catalog_settings/export_products_not_visible_individually';
    public const USE_PRICES_INDEX_PATH = 'streamx_connector_settings/catalog_settings/use_prices_index';
    public const USE_CATALOG_PRICE_RULES_PATH = 'streamx_connector_settings/catalog_settings/use_catalog_price_rules';
    public const ENABLE_RABBIT_MQ = 'streamx_connector_settings/rabbit_mq/enable';

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
        self::setConfigurationValue(self::PRODUCT_ATTRIBUTES_PATH, implode(',', $attributeCodes));
    }

    public static function addIndexedProductAttributes(string ...$attributeCodes): void {
        $attributes = explode(',', self::readDefaultValue(self::PRODUCT_ATTRIBUTES_PATH));
        array_push($attributes, ...$attributeCodes);
        $attributes = array_unique($attributes);
        self::setConfigurationValue(self::PRODUCT_ATTRIBUTES_PATH, implode(',', $attributes));
    }

    public static function allowIndexingAllProductAttributes(): void {
        self::setConfigurationValue(self::PRODUCT_ATTRIBUTES_PATH, '');
    }

    public static function restoreDefaultIndexedProductAttributes(): void {
        self::restoreConfigurationValue(self::PRODUCT_ATTRIBUTES_PATH);
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
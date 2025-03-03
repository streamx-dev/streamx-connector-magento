<?php

namespace StreamX\ConnectorCatalog\test\integration\utils;

trait ConfigurationEditTraits {

    private string $CONFIGURATION_EDIT_ENDPOINT = 'configuration/edit';
    private string $PRODUCT_ATTRIBUTES_PATH = 'streamx_connector_settings/catalog_settings/product_attributes';
    public string $EXPORT_PRODUCTS_NOT_VISIBLE_INDIVIDUALLY_PATH = 'streamx_connector_settings/catalog_settings/export_products_not_visible_individually';

    public function setConfigurationValues(array $pathValueMap): void {
        foreach ($pathValueMap as $path => $value) {
            self::callMagentoConfigurationEditEndpoint($path, $value);
        }
        self::$indexerOperations->flushConfigCache();
    }

    public function restoreConfigurationValues(array $paths): void {
        foreach ($paths as $path) {
            self::callMagentoConfigurationEditEndpoint($path, $this->readDefaultValue($path));
        }
        self::$indexerOperations->flushConfigCache();
    }

    public function setConfigurationValue(string $path, string $value): void {
        $this->setConfigurationValues([$path => $value]);
    }

    public function restoreConfigurationValue(string $path): void {
        $this->restoreConfigurationValues([$path]);
    }

    protected function setIndexedProductAttributes(string ...$attributeCodes): void {
        $this->setConfigurationValue($this->PRODUCT_ATTRIBUTES_PATH, implode(',', $attributeCodes));
    }

    protected function addIndexedProductAttributes(string ...$attributeCodes): void {
        $attributes = explode(',', $this->readDefaultValue($this->PRODUCT_ATTRIBUTES_PATH));
        array_push($attributes, ...$attributeCodes);
        $attributes = array_unique($attributes);
        $this->setConfigurationValue($this->PRODUCT_ATTRIBUTES_PATH, implode(',', $attributes));
    }

    protected function allowIndexingAllProductAttributes(): void {
        $this->setConfigurationValue($this->PRODUCT_ATTRIBUTES_PATH, '');
    }

    protected function restoreDefaultIndexedProductAttributes(): void {
        $this->restoreConfigurationValue($this->PRODUCT_ATTRIBUTES_PATH);
    }

    private function readDefaultValue(string $configurationFieldPath): string {
        $defaultValuesFileContent = FileUtils::readSourceFileContent('src/catalog/etc/config.xml');
        $xmlDoc = simplexml_load_string($defaultValuesFileContent);
        return (string)$xmlDoc->xpath("//$configurationFieldPath")[0];
    }

    private function callMagentoConfigurationEditEndpoint(string $configurationFieldPath, string $value): void {
        self::callMagentoPutEndpoint($this->CONFIGURATION_EDIT_ENDPOINT, [
            'configurationFieldPath' => $configurationFieldPath,
            'value' => $value
        ]);
    }
}
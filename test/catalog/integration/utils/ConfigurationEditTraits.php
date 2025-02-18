<?php

namespace StreamX\ConnectorCatalog\test\integration\utils;

trait ConfigurationEditTraits {

    private string $CONFIGURATION_EDIT_ENDPOINT = 'configuration/edit';
    public string $PRODUCT_ATTRIBUTES_PATH = 'streamx_connector_settings/catalog_settings/product_attributes';
    public string $CHILD_PRODUCT_ATTRIBUTES_PATH = 'streamx_connector_settings/catalog_settings/child_product_attributes';

    public function setConfigurationValue(string $path, string $value): void {
        $this->callMagentoConfigurationEditEndpoint(
            $path,
            $value
        );
    }

    public function restoreConfigurationValue(string $path): void {
        $this->callMagentoConfigurationEditEndpoint(
            $path,
            $this->readDefaultValue($path)
        );
    }

    protected function allowIndexingAllAttributes(): void {
        $this->setIndexedAttributes(
            '',
            ''
        );
    }

    protected function restoreDefaultIndexingAttributes(): void {
        $this->setIndexedAttributes(
            $this->readDefaultValue($this->PRODUCT_ATTRIBUTES_PATH),
            $this->readDefaultValue($this->CHILD_PRODUCT_ATTRIBUTES_PATH)
        );
    }

    private function setIndexedAttributes(string $productAttributes, string $childProductAttributes): void {
        $this->callMagentoConfigurationEditEndpoint($this->PRODUCT_ATTRIBUTES_PATH, $productAttributes);
        $this->callMagentoConfigurationEditEndpoint($this->CHILD_PRODUCT_ATTRIBUTES_PATH, $childProductAttributes);
    }

    private function readDefaultValue(string $configurationFieldPath): string {
        $defaultValuesFileContent = FileUtils::readSourceFileContent('src/catalog/etc/config.xml');
        $xmlDoc = simplexml_load_string($defaultValuesFileContent);
        return (string)$xmlDoc->xpath("//$configurationFieldPath")[0];
    }

    private function callMagentoConfigurationEditEndpoint(string $configurationFieldPath, string $value): void {
        $this->callMagentoPutEndpoint($this->CONFIGURATION_EDIT_ENDPOINT, [
            'configurationFieldPath' => $configurationFieldPath,
            'value' => $value
        ]);
        $this->indexerOperations->flushCache();
    }
}
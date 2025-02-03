<?php

namespace StreamX\ConnectorCatalog\test\integration\utils;

trait ConfigurationEditTraits {

    private string $CONFIGURATION_EDIT_ENDPOINT = 'configuration/edit';
    private string $PRODUCT_ATTRIBUTES_PATH = 'streamx_connector_settings/catalog_settings/product_attributes';
    private string $CHILD_ATTRIBUTES_PATH = 'streamx_connector_settings/catalog_settings/child_attributes';
    public string $ADD_SWATCHES_PATH = 'streamx_connector_settings/catalog_settings/add_swatches_to_configurable_options';

    protected function allowIndexingAllAttributes(): void {
        $this->setIndexedAttributes(
            '',
            ''
        );
    }

    protected function restoreDefaultIndexingAttributes(): void {
        $this->setIndexedAttributes(
            $this->readDefaultValue($this->PRODUCT_ATTRIBUTES_PATH),
            $this->readDefaultValue($this->CHILD_ATTRIBUTES_PATH)
        );
    }

    private function setIndexedAttributes(string $productAttributes, string $childAttributes): void {
        $this->setConfigurationValue($this->PRODUCT_ATTRIBUTES_PATH, $productAttributes);
        $this->setConfigurationValue($this->CHILD_ATTRIBUTES_PATH, $childAttributes);
    }

    public function setConfigurationValue(string $path, string $value): void {
        $this->callMagentoConfigurationEditEndpoint(
            $path,
            $value
        );
    }

    public function restoreConfigurationValue(string $path): void {
        $this->setConfigurationValue(
            $path,
            $this->readDefaultValue($path)
        );
    }

    private function callMagentoConfigurationEditEndpoint(string $configurationFieldPath, string $value): void {
        $this->callMagentoPutEndpoint($this->CONFIGURATION_EDIT_ENDPOINT, [
            'configurationFieldPath' => $configurationFieldPath,
            'value' => $value
        ]);
        $this->indexerOperations->executeCommand('cache:flush');
    }

    private function readDefaultValue(string $configurationFieldPath): string {
        $defaultValuesFileContent = FileUtils::readSourceFileContent('src/catalog/etc/config.xml');
        $xmlDoc = simplexml_load_string($defaultValuesFileContent);
        return (string)$xmlDoc->xpath("//$configurationFieldPath")[0];
    }
}
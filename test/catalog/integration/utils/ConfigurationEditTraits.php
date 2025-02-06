<?php

namespace StreamX\ConnectorCatalog\test\integration\utils;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use SimpleXMLElement;
use StreamX\ConnectorCatalog\test\integration\utils\FileUtils;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoIndexerOperationsExecutor;
use StreamX\ConnectorCatalog\test\integration\utils\MagentoMySqlQueryExecutor;

trait ConfigurationEditTraits {

    private string $CONFIGURATION_EDIT_ENDPOINT = 'configuration/edit';
    private string $PRODUCT_ATTRIBUTES_PATH = 'streamx_connector_settings/catalog_settings/product_attributes';
    private string $CHILD_PRODUCT_ATTRIBUTES_PATH = 'streamx_connector_settings/catalog_settings/child_product_attributes';

    protected function allowIndexingAllAttributes(): void {
        $this->setIndexedAttributes('', '');
    }

    protected function restoreDefaultIndexingAttributes(): void {
        $defaultValuesFileContent = FileUtils::readSourceFileContent('src/catalog/etc/config.xml');
        $xmlDoc = simplexml_load_string($defaultValuesFileContent);
        $defaultProductAttributes = (string) $xmlDoc->xpath('//' . $this->PRODUCT_ATTRIBUTES_PATH)[0];
        $defaultChildProductAttributes = (string) $xmlDoc->xpath('//' . $this->CHILD_PRODUCT_ATTRIBUTES_PATH)[0];

        $this->setIndexedAttributes($defaultProductAttributes, $defaultChildProductAttributes);
    }

    private function setIndexedAttributes(string $productAttributes, string $childProductAttributes): void {
        $this->callMagentoConfigurationEditEndpoint($this->PRODUCT_ATTRIBUTES_PATH, $productAttributes);
        $this->callMagentoConfigurationEditEndpoint($this->CHILD_PRODUCT_ATTRIBUTES_PATH, $childProductAttributes);
        $this->indexerOperations->executeCommand('cache:flush');
    }

    private function callMagentoConfigurationEditEndpoint(string $configurationFieldPath, string $value): void {
        $this->callMagentoPutEndpoint($this->CONFIGURATION_EDIT_ENDPOINT, [
            'configurationFieldPath' => $configurationFieldPath,
            'value' => $value
        ]);
    }
}
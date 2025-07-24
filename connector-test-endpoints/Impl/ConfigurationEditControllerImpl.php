<?php

namespace StreamX\ConnectorTestEndpoints\Impl;

use Magento\Store\Model\ScopeInterface;
use StreamX\ConnectorTestEndpoints\Api\ConfigurationEditControllerInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

class ConfigurationEditControllerImpl implements ConfigurationEditControllerInterface {

    private WriterInterface $configWriter;

    public function __construct(WriterInterface $configWriter) {
        $this->configWriter = $configWriter;
    }

    public function setGlobalConfigurationValue(string $configurationFieldPath, string $value): void {
        $this->configWriter->save($configurationFieldPath, $value);
    }

    public function setStoreLevelConfigurationValue(string $configurationFieldPath, int $storeId, string $value): void {
        $this->configWriter->save($configurationFieldPath, $value, ScopeInterface::SCOPE_STORES, $storeId);
    }

    public function removeStoreLevelConfigurationValue(string $configurationFieldPath, int $storeId): void {
        $this->configWriter->delete($configurationFieldPath, ScopeInterface::SCOPE_STORES, $storeId);
    }
}

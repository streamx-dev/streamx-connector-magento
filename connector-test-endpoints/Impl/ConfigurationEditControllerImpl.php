<?php

namespace StreamX\ConnectorTestEndpoints\Impl;

use Magento\Store\Model\ScopeInterface;
use StreamX\ConnectorTestEndpoints\Api\ConfigurationEditControllerInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

class ConfigurationEditControllerImpl implements ConfigurationEditControllerInterface {

    private WriterInterface $configWriter;
    private TypeListInterface $cacheTypeList;

    public function __construct(WriterInterface $configWriter, TypeListInterface $cacheTypeList) {
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
    }

    /**
     * @inheritdoc
     */
    public function setGlobalConfigurationValue(string $configurationFieldPath, string $value): void {
        $this->configWriter->save($configurationFieldPath, $value);
        $this->flushConfigCache();
    }

    public function setStoreLevelConfigurationValue(string $configurationFieldPath, int $storeId, string $value): void {
        $this->configWriter->save($configurationFieldPath, $value, ScopeInterface::SCOPE_STORES, $storeId);
        $this->flushConfigCache();
    }

    public function removeStoreLevelConfigurationValue(string $configurationFieldPath, int $storeId): void {
        $this->configWriter->delete($configurationFieldPath, ScopeInterface::SCOPE_STORES, $storeId);
        $this->flushConfigCache();
    }

    private function flushConfigCache(): void {
        $this->cacheTypeList->cleanType('config');
    }
}

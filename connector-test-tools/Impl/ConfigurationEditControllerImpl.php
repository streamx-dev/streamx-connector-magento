<?php

namespace StreamX\ConnectorTestTools\Impl;

use StreamX\ConnectorTestTools\Api\ConfigurationEditControllerInterface;
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
    public function setConfigurationValue(string $configurationFieldPath, string $value): void {
        $this->configWriter->save($configurationFieldPath, $value);
        $this->cacheTypeList->cleanType('config');
    }
}

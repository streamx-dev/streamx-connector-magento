<?php

namespace StreamX\ConnectorTestTools\Impl;

use StreamX\ConnectorTestTools\Api\ConfigurationEditControllerInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

class ConfigurationEditControllerImpl implements ConfigurationEditControllerInterface {

    private WriterInterface $configWriter;

    public function __construct(WriterInterface $configWriter) {
        $this->configWriter = $configWriter;
    }

    /**
     * @inheritdoc
     */
    public function setConfigurationValue(string $configurationFieldPath, string $value): void {
        $this->configWriter->save($configurationFieldPath, $value);
    }
}

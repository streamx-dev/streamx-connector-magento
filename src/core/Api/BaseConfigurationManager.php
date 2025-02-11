<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Api;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Store\Model\ScopeInterface;

abstract class BaseConfigurationManager extends BaseConfigurationReader {

    private WriterInterface $configWriter;

    public function __construct(ScopeConfigInterface $scopeConfig, string $configXmlNode, WriterInterface $configWriter) {
        parent::__construct($scopeConfig, $configXmlNode);
        $this->configWriter = $configWriter;
    }

    public function setGlobalConfigValue(string $configField, $configValue): void {
        $path = $this->getConfigFieldFullPath($configField);
        $this->configWriter->save($path, $configValue);
    }
}

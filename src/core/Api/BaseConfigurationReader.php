<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Api;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

abstract class BaseConfigurationReader {

    private const BASE_CONFIG_XML_NODE = 'streamx_connector_settings';

    private ScopeConfigInterface $scopeConfig;
    private string $configXmlNodePath;

    public function __construct(ScopeConfigInterface $scopeConfig, string $configXmlNode) {
        $this->scopeConfig = $scopeConfig;
        $this->configXmlNodePath = self::BASE_CONFIG_XML_NODE . '/' . $configXmlNode;
    }

    protected static function splitCommaSeparatedValueToArray(?string $commaSeparatedValue): array {
        return $commaSeparatedValue === null || $commaSeparatedValue === ''
            ? []
            : explode(',', $commaSeparatedValue);
    }

    /**
     * @return mixed|null
     */
    protected function getGlobalConfigValue(string $configField) {
        $path = $this->getConfigFieldFullPath($configField);
        return $this->scopeConfig->getValue($path);
    }

    /**
     * @return mixed|null
     */
    protected function getWebsiteLevelConfigValue(string $configField, int $websiteId) {
        $path = $this->getConfigFieldFullPath($configField);
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_WEBSITE, $websiteId);
    }

    /**
     * @return mixed|null
     */
    protected function getStoreLevelConfigValue(string $configField, int $storeId) {
        $path = $this->getConfigFieldFullPath($configField);
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    protected function getConfigFieldFullPath(string $configField): string {
        return $this->configXmlNodePath . '/' . $configField;
    }
}

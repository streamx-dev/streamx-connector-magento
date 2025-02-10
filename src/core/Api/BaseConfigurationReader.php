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

    public function getBoolConfigValue(string $configField, int $storeId = null): bool {
        return (bool) $this->getConfigValue($configField, $storeId);
    }

    public function getIntConfigValue(string $configField, int $storeId = null): int {
        return (int) $this->getConfigValue($configField, $storeId);
    }

    public function getStringConfigValue(string $configField, int $storeId = null): string {
        return (string) $this->getConfigValue($configField, $storeId);
    }

    public function getNullableStringConfigValue(string $configField, int $storeId = null): ?string {
        return $this->getConfigValue($configField, $storeId);
    }

    public function getArrayConfigValue(string $configField, int $storeId = null): array {
        $commaSeparatedValue = $this->getConfigValue($configField, $storeId);

        return null === $commaSeparatedValue || '' === $commaSeparatedValue
            ? []
            : explode(',', $commaSeparatedValue);
    }

    /**
     * @return mixed
     */
    private function getConfigValue(string $configField, int $storeId = null) {
        // TODO: test with multistores magento
        $path = $this->getConfigFieldFullPath($configField);
        $scopeType = $storeId ? ScopeInterface::SCOPE_STORES : ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        return $this->scopeConfig->getValue($path, $scopeType, $storeId);
    }

    protected function getConfigFieldFullPath(string $configField): string {
        return $this->configXmlNodePath . '/' . $configField;
    }
}

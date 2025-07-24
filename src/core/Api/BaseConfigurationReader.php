<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Api;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Store\Model\ScopeInterface;

abstract class BaseConfigurationReader {

    private const BASE_CONFIG_XML_NODE = 'streamx_connector_settings';

    private ResourceConnection $connection;
    private ScopeConfigInterface $scopeConfig;
    private string $configXmlNodePath;

    public function __construct(ResourceConnection $connection, ScopeConfigInterface $scopeConfig, string $configXmlNode) {
        $this->connection = $connection;
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
        $value = $this->readConfigValueFromDb($configField, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);

        if ($value !== null) {
            return $value;
        }

        return $this->getDefaultValue($configField);
    }

    /**
     * @return mixed|null
     */
    protected function getWebsiteLevelConfigValue(string $configField, int $websiteId) {
        $value = $this->readConfigValueFromDb($configField, ScopeInterface::SCOPE_WEBSITES, $websiteId);

        if ($value !== null) {
            return $value;
        }

        return $this->getGlobalConfigValue($configField);
    }

    /**
     * @return mixed|null
     */
    protected function getStoreLevelConfigValue(string $configField, int $storeId) {
        $value = $this->readConfigValueFromDb($configField, ScopeInterface::SCOPE_STORES, $storeId);

        if ($value !== null) {
            return $value;
        }

        $websiteId = (int)$this->connection->getConnection()->fetchOne('
            SELECT website_id
              FROM store
             WHERE store_id = ?
        ', $storeId);

        return $this->getWebsiteLevelConfigValue($configField, $websiteId);
    }

    /**
     * @return mixed|null
     */
    private function readConfigValueFromDb(string $configField, string $scopeName, int $entityId) {
        $path = self::getConfigFieldFullPath($configField);
        $value = $this->connection->getConnection()->fetchOne('
            SELECT value
              FROM core_config_data
             WHERE scope = ?
               AND scope_id = ?
               AND path = ?
        ', [$scopeName, $entityId, $path]);

        // fetchOne returns false if no matching rows
        return $value === false
            ? null
            : $value;
    }

    private function getDefaultValue(string $configField) {
        return $this->scopeConfig->getValue(self::getConfigFieldFullPath($configField));
    }

    protected function getConfigFieldFullPath(string $configField): string {
        return $this->configXmlNodePath . '/' . $configField;
    }
}

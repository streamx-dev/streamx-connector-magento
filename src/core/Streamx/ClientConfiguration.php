<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Streamx;

use StreamX\ConnectorCore\Api\Client\ConfigurationInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class ClientConfiguration implements ConfigurationInterface {

    private const STREAMX_CLIENT_CONFIG_XML_PREFIX = 'streamx_connector_settings/streamx_client';

    private ScopeConfigInterface $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig) {
        $this->scopeConfig = $scopeConfig;
    }

    public function getIngestionBaseUrl(int $storeId): string {
        return $this->getConfigParam('ingestion_base_url', $storeId);
    }

    public function getChannelName(int $storeId): string {
        return $this->getConfigParam('channel_name', $storeId);
    }

    public function getChannelSchemaName(int $storeId): string {
        return $this->getConfigParam('channel_schema_name', $storeId);
    }

    public function getProductKeyPrefix(int $storeId): string {
        return $this->getConfigParam('product_key_prefix', $storeId);
    }

    public function getCategoryKeyPrefix(int $storeId): string {
        return $this->getConfigParam('category_key_prefix', $storeId);
    }

    public function getAuthToken(int $storeId): ?string {
        return $this->getConfigParam('auth_token', $storeId);
    }

    private function getConfigParam(string $configField, $storeId): ?string {
        $path = self::STREAMX_CLIENT_CONFIG_XML_PREFIX . '/' . $configField;
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }
}

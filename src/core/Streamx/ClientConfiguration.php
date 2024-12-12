<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Streamx;

use StreamX\ConnectorCore\Api\Client\ConfigurationInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class ClientConfiguration implements ConfigurationInterface {
    const STREAMX_CLIENT_CONFIG_XML_PREFIX = 'streamx_connector_settings/streamx_client';
    const INGESTION_BASE_URL_FIELD = 'ingestion_base_url';
    const CHANNEL_NAME_FIELD = 'channel_name';
    const CHANNEL_SCHEMA_NAME_FIELD = 'channel_schema_name';
    const AUTH_TOKEN_FIELD = 'auth_token';

    private ScopeConfigInterface $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig) {
        $this->scopeConfig = $scopeConfig;
    }

    public function getOptions(int $storeId): array {
        return [
            self::INGESTION_BASE_URL_FIELD => $this->getIngestionBaseUrl($storeId),
            self::CHANNEL_NAME_FIELD => $this->getChannelName($storeId),
            self::CHANNEL_SCHEMA_NAME_FIELD => $this->getChannelSchemaName($storeId),
            self::AUTH_TOKEN_FIELD => $this->getAuthToken($storeId)
        ];
    }

    public function getIngestionBaseUrl(int $storeId): string {
        return $this->getConfigParam(self::INGESTION_BASE_URL_FIELD, $storeId);
    }

    public function getChannelName(int $storeId): string {
        return $this->getConfigParam(self::CHANNEL_NAME_FIELD, $storeId);
    }

    public function getChannelSchemaName(int $storeId): string {
        return $this->getConfigParam(self::CHANNEL_SCHEMA_NAME_FIELD, $storeId);
    }

    public function getAuthToken(int $storeId): ?string {
        return $this->getConfigParam(self::AUTH_TOKEN_FIELD, $storeId);
    }

    private function getConfigParam(string $configField, $storeId): ?string {
        $path = self::STREAMX_CLIENT_CONFIG_XML_PREFIX . '/' . $configField;
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }
}

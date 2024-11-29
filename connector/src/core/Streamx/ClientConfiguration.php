<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Streamx;

use StreamX\ConnectorCore\Api\Client\ConfigurationInterface as ClientConfigurationInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class ClientConfiguration implements ClientConfigurationInterface {
    const STREAMX_CLIENT_CONFIG_XML_PREFIX = 'streamx_indexer_settings/streamx_client';
    const INGESTION_BASE_URL_FIELD = 'ingestion_base_url';
    const PAGES_SCHEMA_NAME_FIELD = 'pages_schema_name';

    private ScopeConfigInterface $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig) {
        $this->scopeConfig = $scopeConfig;
    }

    public function getOptions(int $storeId): array {
        return [
            self::INGESTION_BASE_URL_FIELD => $this->getIngestionBaseUrl($storeId),
            self::PAGES_SCHEMA_NAME_FIELD => $this->getPagesSchemaName($storeId)
        ];
    }

    public function getIngestionBaseUrl(int $storeId): string {
        return $this->getConfigParam(self::INGESTION_BASE_URL_FIELD, $storeId);
    }

    public function getPagesSchemaName(int $storeId): string {
        return $this->getConfigParam(self::PAGES_SCHEMA_NAME_FIELD, $storeId);
    }

    private function getConfigParam(string $configField, $storeId): string {
        $path = self::STREAMX_CLIENT_CONFIG_XML_PREFIX . '/' . $configField;
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }
}

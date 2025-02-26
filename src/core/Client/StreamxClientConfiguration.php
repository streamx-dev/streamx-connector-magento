<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client;

use Magento\Framework\App\Config\ScopeConfigInterface;
use StreamX\ConnectorCore\Api\BaseConfigurationReader;

class StreamxClientConfiguration extends BaseConfigurationReader
{
    public function __construct(ScopeConfigInterface $scopeConfig) {
        parent::__construct($scopeConfig, 'streamx_client');
    }

    public function getIngestionBaseUrl(int $storeId): string {
        return (string)$this->getStoreLevelConfigValue('ingestion_base_url', $storeId);
    }

    public function getChannelName(int $storeId): string {
        return (string)$this->getStoreLevelConfigValue('channel_name', $storeId);
    }

    public function getChannelSchemaName(int $storeId): string {
        return (string)$this->getStoreLevelConfigValue('channel_schema_name', $storeId);
    }

    public function getAuthToken(int $storeId): ?string {
        return $this->getStoreLevelConfigValue('auth_token', $storeId);
    }

    public function shouldDisableCertificateValidation(int $storeId): bool {
        return (bool)$this->getStoreLevelConfigValue('disable_certificate_validation', $storeId);
    }
}

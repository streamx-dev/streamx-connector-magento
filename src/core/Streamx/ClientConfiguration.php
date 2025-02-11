<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Streamx;

use Magento\Framework\App\Config\ScopeConfigInterface;
use StreamX\ConnectorCore\Api\BaseConfigurationProvider;

class ClientConfiguration extends BaseConfigurationProvider
{
    public function __construct(ScopeConfigInterface $scopeConfig) {
        parent::__construct($scopeConfig, 'streamx_client');
    }

    public function getIngestionBaseUrl(int $storeId): string {
        return $this->getStringConfigValue('ingestion_base_url', $storeId);
    }

    public function getChannelName(int $storeId): string {
        return $this->getStringConfigValue('channel_name', $storeId);
    }

    public function getChannelSchemaName(int $storeId): string {
        return $this->getStringConfigValue('channel_schema_name', $storeId);
    }

    public function getProductKeyPrefix(int $storeId): string {
        return $this->getStringConfigValue('product_key_prefix', $storeId);
    }

    public function getCategoryKeyPrefix(int $storeId): string {
        return $this->getStringConfigValue('category_key_prefix', $storeId);
    }

    public function getAuthToken(int $storeId): ?string {
        return $this->getNullableStringConfigValue('auth_token', $storeId);
    }

    public function shouldDisableCertificateValidation(int $storeId): bool {
        return $this->getBoolConfigValue('disable_certificate_validation', $storeId);
    }
}

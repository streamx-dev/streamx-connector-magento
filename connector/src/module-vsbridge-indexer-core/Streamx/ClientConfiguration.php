<?php declare(strict_types=1);

namespace Divante\VsbridgeIndexerCore\Streamx;

use Divante\VsbridgeIndexerCore\Api\Client\ConfigurationInterface as ClientConfigurationInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class ClientConfiguration implements ClientConfigurationInterface {
    const ES_CLIENT_CONFIG_XML_PREFIX = 'vsbridge_indexer_settings/es_client';

    private ScopeConfigInterface $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig) {
        $this->scopeConfig = $scopeConfig;
    }

    public function getOptions(int $storeId): array {
        return [
            'host' => $this->getHost($storeId),
            'port' => $this->getPort($storeId),
            'scheme' => $this->getScheme($storeId),
            'enable_http_auth' => $this->isHttpAuthEnabled($storeId),
            'auth_user' => $this->getHttpAuthUser($storeId),
            'auth_pwd' => $this->getHttpAuthPassword($storeId),
        ];
    }

    public function getHost(int $storeId): string {
        return (string)$this->getConfigParam('host', $storeId);
    }

    public function getPort(int $storeId): string {
        return (string)$this->getConfigParam('port', $storeId);
    }

    public function getScheme(int $storeId): string {
        return $this->isHttpsModeEnabled($storeId) ? 'https' : 'http';
    }

    public function isHttpsModeEnabled(int $storeId): bool {
        return (bool)$this->getConfigParam('enable_https_mode', $storeId);
    }

    public function isHttpAuthEnabled(int $storeId): bool {
        $authEnabled = (bool)$this->getConfigParam('enable_http_auth', $storeId);
        return $authEnabled && !empty($this->getHttpAuthUser($storeId)) && !empty($this->getHttpAuthPassword($storeId));
    }

    public function getHttpAuthUser(int $storeId): string {
        return (string)$this->getConfigParam('auth_user', $storeId);
    }

    public function getHttpAuthPassword(int $storeId): string {
        return (string)$this->getConfigParam('auth_pwd', $storeId);
    }

    private function getConfigParam(string $configField, $storeId): ?string {
        $path = self::ES_CLIENT_CONFIG_XML_PREFIX . '/' . $configField;
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }
}

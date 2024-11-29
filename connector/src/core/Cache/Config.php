<?php

declare(strict_types=1);

namespace Divante\VsbridgeIndexerCore\Cache;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * @inheritdoc
 */
class Config implements ConfigInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function clearCache(int $storeId): bool
    {
        return (bool) $this->getConfigParam(self::CLEAR_CACHE_FIELD, $storeId);
    }

    public function getInvalidateEntitiesBatchSize(int $storeId): int
    {
        return (int) $this->getConfigParam(self::INVALIDATE_CACHE_ENTITIES_BATCH_SIZE_FIELD, $storeId);
    }

    public function getVsfBaseUrl(int $storeId): string
    {
        return (string) $this->getConfigParam(self::VSF_BASE_URL_FIELD, $storeId);
    }

    public function getInvalidateCacheKey(int $storeId): string
    {
        return (string) $this->getConfigParam(self::INVALIDATE_CACHE_FIELD, $storeId);
    }

    public function getTimeout(int $storeId): int
    {
        return (int) $this->getConfigParam(self::CONNECTION_TIMEOUT_FIELD, $storeId);
    }

    public function getConnectionOptions(int $storeId): array
    {
        return ['timeout' => $this->getTimeout($storeId)];
    }

    private function getConfigParam(string $configField, int $storeId): ?string
    {
        $path = self::CACHE_SETTINGS_XML_PREFIX . '/' . $configField;

        return $this->scopeConfig->getValue($path, 'stores', $storeId);
    }
}

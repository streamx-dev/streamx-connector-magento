<?php

declare(strict_types=1);

namespace StreamX\ConnectorCore\Cache;

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

    private function getConfigParam(string $configField, int $storeId): ?string
    {
        $path = self::CACHE_SETTINGS_XML_PREFIX . '/' . $configField;

        return $this->scopeConfig->getValue($path, 'stores', $storeId);
    }
}

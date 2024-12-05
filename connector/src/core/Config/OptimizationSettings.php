<?php

namespace StreamX\ConnectorCore\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;

class OptimizationSettings
{
    const OPTIMIZATION_SETTINGS_CONFIG_XML_PREFIX = 'streamx_indexer_settings/optimization_settings';

    private ScopeConfigInterface $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function checkClusterHealth(): bool
    {
        return (bool) $this->getConfigParam('cluster_health');
    }

    public function checkMaxBulkQueueRequirement(): bool
    {
        return (bool) $this->getConfigParam('max_bulk_queue_requirement');
    }

    private function getConfigParam(string $configField): ?string
    {
        $path = self::OPTIMIZATION_SETTINGS_CONFIG_XML_PREFIX . '/' . $configField;

        return $this->scopeConfig->getValue($path);
    }
}

<?php

namespace StreamX\ConnectorCore\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;

class OptimizationSettings
{
    const OPTIMIZATION_SETTINGS_CONFIG_XML_PREFIX = 'streamx_indexer_settings/optimization_settings';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

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

    public function changeRefreshInterval(): bool
    {
        return (bool) $this->getConfigParam('refresh_interval');
    }

    public function getDefaultRefreshInterval(): string
    {
        return $this->getConfigParam('refresh_interval_value') . 's';
    }

    private function getConfigParam(string $configField): ?string
    {
        $path = self::OPTIMIZATION_SETTINGS_CONFIG_XML_PREFIX . '/' . $configField;

        return $this->scopeConfig->getValue($path);
    }
}

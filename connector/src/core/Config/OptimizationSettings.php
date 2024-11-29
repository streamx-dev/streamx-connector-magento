<?php

namespace StreamX\ConnectorCore\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;

class OptimizationSettings
{
    const OPTIMIZATION_SETTINGS_CONFIG_XML_PREFIX = 'vsbridge_indexer_settings/optimization_settings';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return bool
     */
    public function checkClusterHealth()
    {
        return (bool) $this->getConfigParam('cluster_health');
    }

    /**
     * @return bool
     */
    public function checkMaxBulkQueueRequirement()
    {
        return (bool) $this->getConfigParam('max_bulk_queue_requirement');
    }

    /**
     * @return bool
     */
    public function changeNumberOfReplicas()
    {
        return (bool) $this->getConfigParam('number_of_replicas');
    }

    public function getDefaultNumberOfReplicas(): string
    {
        return $this->getConfigParam('number_of_replicas_value');
    }

    /**
     * @return bool
     */
    public function changeRefreshInterval()
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

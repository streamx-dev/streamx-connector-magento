<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use StreamX\ConnectorCore\Api\BaseConfigurationReader;

class OptimizationSettings extends BaseConfigurationReader
{
    public function __construct(ResourceConnection $connection, ScopeConfigInterface $scopeConfig) {
        parent::__construct($connection, $scopeConfig, 'optimization_settings');
    }

    public function shouldPerformStreamxAvailabilityCheck(): bool {
        return (bool)$this->getGlobalConfigValue('should_perform_streamx_availability_check');
    }

    public function getBatchIndexingSize(): int {
        return (int)$this->getGlobalConfigValue('batch_indexing_size');
    }

    function getBatchIndexingSizeConfigFieldPath(): string {
        return $this->getConfigFieldFullPath('batch_indexing_size');
    }
}

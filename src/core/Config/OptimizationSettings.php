<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use StreamX\ConnectorCore\Api\BaseConfigurationProvider;

class OptimizationSettings extends BaseConfigurationProvider
{
    public function __construct(ScopeConfigInterface $scopeConfig) {
        parent::__construct($scopeConfig, 'optimization_settings');
    }

    public function shouldPerformStreamxAvailabilityCheck(): bool {
        return $this->getBoolConfigValue('should_perform_streamx_availability_check');
    }

    public function getBatchIndexingSize(): int {
        return $this->getIntConfigValue('batch_indexing_size');
    }
}

<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\System;

use Magento\Framework\App\Config\ScopeConfigInterface;
use StreamX\ConnectorCore\Api\BaseConfigurationProvider;

class GeneralConfig extends BaseConfigurationProvider
{
    public function __construct(ScopeConfigInterface $scopeConfig) {
        parent::__construct($scopeConfig, 'general_settings');
    }

    public function getStoresToIndex(): array {
        return $this->getArrayConfigValue('allowed_stores');
    }

    public function isEnabled(): bool {
        return $this->getBoolConfigValue('enable');
    }
}

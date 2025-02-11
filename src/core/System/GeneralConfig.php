<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\System;

use Magento\Framework\App\Config\ScopeConfigInterface;
use StreamX\ConnectorCore\Api\BaseConfigurationReader;

class GeneralConfig extends BaseConfigurationReader
{
    public function __construct(ScopeConfigInterface $scopeConfig) {
        parent::__construct($scopeConfig, 'general_settings');
    }

    public function getStoresToIndex(): array {
        return parent::splitCommaSeparatedValueToArray(
            $this->getGlobalConfigValue('allowed_stores')
        );
    }

    public function isEnabled(): bool {
        return (bool)$this->getGlobalConfigValue('enable');
    }
}

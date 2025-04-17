<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\System;

use Magento\Framework\App\Config\ScopeConfigInterface;
use StreamX\ConnectorCore\Api\BaseConfigurationReader;

class GeneralConfig extends BaseConfigurationReader
{
    public function __construct(ScopeConfigInterface $scopeConfig) {
        parent::__construct($scopeConfig, 'general_settings');
    }

    public function isEnabled(): bool {
        return (bool)$this->getGlobalConfigValue('enable');
    }

    /**
     * @return int[]
     */
    public function getStoresToIndex(int $websiteId): array {
        $storeIds = parent::splitCommaSeparatedValueToArray(
            $this->getWebsiteLevelConfigValue('allowed_stores', $websiteId)
        );
        return array_map('intval', $storeIds);
    }
}

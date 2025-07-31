<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\System;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use StreamX\ConnectorCore\Api\BaseConfigurationReader;

class GeneralConfig extends BaseConfigurationReader
{
    public function __construct(ResourceConnection $connection, ScopeConfigInterface $scopeConfig) {
        parent::__construct($connection, $scopeConfig, 'general_settings');
    }

    public function isEnabled(): bool {
        return (bool)$this->getGlobalConfigValue('enable');
    }

    /**
     * @return int[]
     */
    public function getIndexedStores(int $websiteId): array {
        $storeIds = parent::splitCommaSeparatedValueToArray(
            $this->getWebsiteLevelConfigValue('indexed_stores', $websiteId)
        );
        return array_map('intval', $storeIds);
    }
}

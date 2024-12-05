<?php

namespace StreamX\ConnectorCore\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;

class IndicesSettings
{
    const INDICES_SETTINGS_CONFIG_XML_PREFIX = 'streamx_indexer_settings/indices_settings';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function getBatchIndexingSize(): int
    {
        return (int) $this->getConfigParam('batch_indexing_size');
    }

    private function getConfigParam(string $configField): ?string
    {
        $path = self::INDICES_SETTINGS_CONFIG_XML_PREFIX . '/' . $configField;

        return $this->scopeConfig->getValue($path);
    }
}

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

    public function getIndexNamePrefix(): string
    {
        return (string) $this->getConfigParam('index_name');
    }

    public function getIndexIdentifier(): string
    {
        return (string) $this->getConfigParam('index_identifier');
    }

    /**
     * @return bool
     */
    public function addIdentifierToDefaultStoreView()
    {
        return (bool) $this->getConfigParam('add_identifier_to_default');
    }

    public function getBatchIndexingSize(): int
    {
        return (int) $this->getConfigParam('batch_indexing_size');
    }

    public function getFieldsLimit(): int
    {
        return (int) $this->getConfigParam('fields_limit');
    }

    private function getConfigParam(string $configField): ?string
    {
        $path = self::INDICES_SETTINGS_CONFIG_XML_PREFIX . '/' . $configField;

        return $this->scopeConfig->getValue($path);
    }
}

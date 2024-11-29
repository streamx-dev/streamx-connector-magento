<?php

namespace Divante\VsbridgeIndexerCore\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;

class IndicesSettings
{
    const INDICES_SETTINGS_CONFIG_XML_PREFIX = 'vsbridge_indexer_settings/indices_settings';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return string
     */
    public function getIndexNamePrefix()
    {
        return (string) $this->getConfigParam('index_name');
    }

    /**
     * @return string
     */
    public function getIndexIdentifier()
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

    /**
     * @return int
     */
    public function getBatchIndexingSize()
    {
        return (int) $this->getConfigParam('batch_indexing_size');
    }

    /**
     * @return int
     */
    public function getFieldsLimit()
    {
        return (int) $this->getConfigParam('fields_limit');
    }

    /**
     * @return string|null
     */
    private function getConfigParam(string $configField)
    {
        $path = self::INDICES_SETTINGS_CONFIG_XML_PREFIX . '/' . $configField;

        return $this->scopeConfig->getValue($path);
    }
}

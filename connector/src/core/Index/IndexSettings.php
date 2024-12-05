<?php

namespace StreamX\ConnectorCore\Index;

use StreamX\ConnectorCore\Index\Indicies\Config as IndicesConfig;
use StreamX\ConnectorCore\Config\IndicesSettings;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

class IndexSettings
{
    public const INDEX_NAME_PREFIX = 'streamx_storefront_catalog';

    private StoreManagerInterface $storeManager;
    private IndicesConfig $indicesConfig;
    private IndicesSettings $configuration;
    private DateTimeFactory $dateTimeFactory;

    public function __construct(
        StoreManagerInterface $storeManager,
        IndicesConfig $config,
        IndicesSettings $settingsConfig,
        DateTimeFactory $dateTimeFactory
    ) {
        $this->indicesConfig = $config;
        $this->configuration = $settingsConfig;
        $this->storeManager = $storeManager;
        $this->dateTimeFactory = $dateTimeFactory;
    }

    public function getBatchIndexingSize(): int
    {
        return $this->configuration->getBatchIndexingSize();
    }

    public function getIndicesConfig(): array
    {
        return $this->indicesConfig->get();
    }

    public function createIndexName(StoreInterface $store): string
    {
        $indexNamePrefix = self::INDEX_NAME_PREFIX;
        $storeIdentifier = $this->getStoreIdentifier($store);

        if ($storeIdentifier) {
            $indexNamePrefix .= '_' . $storeIdentifier;
        }

        $name = strtolower($indexNamePrefix);
        $currentDate = $this->dateTimeFactory->create();

        return $name . '_' . $currentDate->getTimestamp();
    }

    private function getStoreIdentifier(StoreInterface $store): string
    {
        if (!$this->configuration->addIdentifierToDefaultStoreView()) {
            $defaultStoreView = $this->storeManager->getDefaultStoreView();

            if ($defaultStoreView->getId() === $store->getId()) {
                return '';
            }
        }

        return (string) $store->getId();
    }
}

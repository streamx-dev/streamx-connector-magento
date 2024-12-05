<?php

namespace StreamX\ConnectorCore\Index;

use StreamX\ConnectorCore\Index\Indicies\Config as IndicesConfig;
use StreamX\ConnectorCore\Config\IndicesSettings;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

class IndexSettings
{
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
        $name = $this->getIndexAlias($store);
        $currentDate = $this->dateTimeFactory->create();

        return $name . '_' . $currentDate->getTimestamp();
    }

    public function getIndexAlias(StoreInterface $store): string
    {
        $indexNamePrefix = $this->configuration->getIndexNamePrefix();
        $storeIdentifier = $this->getStoreIdentifier($store);

        if ($storeIdentifier) {
            $indexNamePrefix .= '_' . $storeIdentifier;
        }

        return strtolower($indexNamePrefix);
    }

    private function getStoreIdentifier(StoreInterface $store): string
    {
        if (!$this->configuration->addIdentifierToDefaultStoreView()) {
            $defaultStoreView = $this->storeManager->getDefaultStoreView();

            if ($defaultStoreView->getId() === $store->getId()) {
                return '';
            }
        }

        $indexIdentifier = $this->configuration->getIndexIdentifier();

        return ('code' === $indexIdentifier) ? $store->getCode() : (string) $store->getId();
    }
}

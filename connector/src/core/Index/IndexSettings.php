<?php

namespace Divante\VsbridgeIndexerCore\Index;

use Divante\VsbridgeIndexerCore\Index\Indicies\Config as IndicesConfig;
use Divante\VsbridgeIndexerCore\Config\IndicesSettings;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

class IndexSettings
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var IndicesConfig
     */
    private $indicesConfig;

    /**
     * @var IndicesSettings
     */
    private $configuration;

    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

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

    public function getEsConfig(): array
    {
        return [
            'index.mapping.total_fields.limit' => $this->configuration->getFieldsLimit(),
            'analysis' => [
                'analyzer' => [
                    'autocomplete' => [
                        'tokenizer' => 'autocomplete',
                        'filter' => ['lowercase'],
                    ],
                    'autocomplete_search' => [
                        'tokenizer'=> 'lowercase'
                    ]
                ],
                'tokenizer' => [
                    'autocomplete' => [
                        'type' => 'edge_ngram',
                        'min_gram' => 2,
                        'max_gram' => 10,
                        'token_chars' => ['letter'],
                    ]
                ]
            ]
        ];
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

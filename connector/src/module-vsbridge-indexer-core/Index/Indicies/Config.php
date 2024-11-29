<?php

namespace Divante\VsbridgeIndexerCore\Index\Indicies;

use Divante\VsbridgeIndexerCore\Api\Index\TypeInterfaceFactory as TypeFactoryInterface;
use Divante\VsbridgeIndexerCore\Indexer\DataProviderProcessorFactory;
use Divante\VsbridgeIndexerCore\Indexer\MappingProcessorFactory;

class Config
{
    /**
     * Factory used to build mapping types.
     *
     * @var TypeFactoryInterface
     */
    private $typeFactory;

    /**
     * @var DataProviderProcessorFactory
     */
    private $dataProviderFactoryProcessor;

    /**
     * @var MappingProcessorFactory
     */
    private $mappingProviderProcessorFactory;

    /**
     * @var \Divante\VsbridgeIndexerCore\Indexer\DataProvider\TransactionKey
     */
    private $transactionKey;

    /**
     * Config\Data
     */
    private $configData;

    public function __construct(
        Config\Data $configData,
        \Divante\VsbridgeIndexerCore\Indexer\DataProvider\TransactionKey $transactionKey,
        TypeFactoryInterface $typeInterfaceFactory,
        MappingProcessorFactory $mappingProcessorFactory,
        DataProviderProcessorFactory $dataProviderFactoryProcessor
    ) {
        $this->configData = $configData;
        $this->transactionKey = $transactionKey;
        $this->mappingProviderProcessorFactory = $mappingProcessorFactory;
        $this->dataProviderFactoryProcessor = $dataProviderFactoryProcessor;
        $this->typeFactory = $typeInterfaceFactory;
    }

    public function get(): array
    {
        $configData = $this->configData->get();
        $indicesConfig = [];

        foreach ($configData as $indexIdentifier => $indexConfig) {
            $indicesConfig[$indexIdentifier] = $this->initIndexConfig($indexConfig);
        }

        return $indicesConfig;
    }

    private function initIndexConfig(array $indexConfigData): array
    {
        $types = [];

        foreach ($indexConfigData['types'] as $typeName => $typeConfigData) {
            $dataProviders = ['transaction_key' => $this->transactionKey];

            foreach ($typeConfigData['data_providers'] as $dataProviderName => $dataProviderClass) {
                $dataProviders[$dataProviderName] =
                    $this->dataProviderFactoryProcessor->get($dataProviderClass);
            }

            $mapping = null;

            if (isset($typeConfigData['mapping'][0])) {
                $mapping = $this->mappingProviderProcessorFactory->get($typeConfigData['mapping'][0]);
            }

            $types[$typeName] = $this->typeFactory->create(
                [
                    'name' => $typeName,
                    'dataProviders' => $dataProviders,
                    'mapping' => $mapping,
                ]
            );
        }

        return ['types' => $types];
    }
}

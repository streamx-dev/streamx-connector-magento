<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Indexer;

use Psr\Log\LoggerInterface;
use StreamX\ConnectorCatalog\Model\Indexer\DataLoader\ProductDataLoader;
use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCore\Api\IndexersConfigInterface;
use StreamX\ConnectorCore\Config\OptimizationSettings;
use StreamX\ConnectorCore\Indexer\BaseStreamxIndexer;
use StreamX\ConnectorCore\Indexer\IndexableStoresProvider;
use StreamX\ConnectorCore\Client\StreamxClientConfiguration;
use StreamX\ConnectorCore\System\GeneralConfig;

class ProductsIndexer extends BaseStreamxIndexer
{
    public function __construct(
        GeneralConfig $connectorConfig,
        IndexableStoresProvider $indexableStoresProvider,
        ProductDataLoader $dataLoader,
        LoggerInterface $logger,
        OptimizationSettings $optimizationSettings,
        StreamxClientConfiguration $clientConfiguration,
        IndexersConfigInterface $indexersConfig
    ) {
        parent::__construct(
            $connectorConfig,
            $indexableStoresProvider,
            $dataLoader,
            $logger,
            $optimizationSettings,
            $clientConfiguration,
            $indexersConfig->getByName(ProductProcessor::INDEXER_ID)
        );
    }
}
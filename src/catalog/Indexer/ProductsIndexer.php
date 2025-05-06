<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Indexer;

use Psr\Log\LoggerInterface;
use StreamX\ConnectorCatalog\Model\Indexer\DataLoader\ProductDataLoader;
use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCore\Api\IndexersConfigInterface;
use StreamX\ConnectorCore\Client\StreamxAvailabilityCheckerFactory;
use StreamX\ConnectorCore\Client\StreamxClientFactory;
use StreamX\ConnectorCore\Config\OptimizationSettings;
use StreamX\ConnectorCore\Indexer\BaseStreamxIndexer;
use StreamX\ConnectorCore\Indexer\IndexedStoresProvider;
use StreamX\ConnectorCore\System\GeneralConfig;

class ProductsIndexer extends BaseStreamxIndexer
{
    public function __construct(
        GeneralConfig $connectorConfig,
        IndexedStoresProvider $indexedStoresProvider,
        ProductDataLoader $dataLoader,
        LoggerInterface $logger,
        OptimizationSettings $optimizationSettings,
        StreamxClientFactory $streamxClientFactory,
        StreamxAvailabilityCheckerFactory $streamxAvailabilityCheckerFactory,
        IndexersConfigInterface $indexersConfig
    ) {
        parent::__construct(
            $connectorConfig,
            $indexedStoresProvider,
            $dataLoader,
            $logger,
            $optimizationSettings,
            $streamxClientFactory,
            $streamxAvailabilityCheckerFactory,
            $indexersConfig->getById(ProductProcessor::INDEXER_ID)
        );
    }
}
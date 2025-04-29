<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Indexer;

use Magento\Framework\Indexer\IndexerRegistry;
use Psr\Log\LoggerInterface;
use StreamX\ConnectorCatalog\Model\Indexer\DataLoader\CategoryDataLoader;
use StreamX\ConnectorCore\Api\IndexersConfigInterface;
use StreamX\ConnectorCore\Client\StreamxAvailabilityCheckerFactory;
use StreamX\ConnectorCore\Client\StreamxClientFactory;
use StreamX\ConnectorCore\Config\OptimizationSettings;
use StreamX\ConnectorCore\Indexer\BaseStreamxIndexer;
use StreamX\ConnectorCore\Indexer\IndexedStoresProvider;
use StreamX\ConnectorCore\System\GeneralConfig;

class CategoryIndexer extends BaseStreamxIndexer {

    public const INDEXER_ID = 'streamx_category_indexer';

    public function __construct(
        GeneralConfig $connectorConfig,
        IndexedStoresProvider $indexedStoresProvider,
        CategoryDataLoader $dataLoader,
        LoggerInterface $logger,
        OptimizationSettings $optimizationSettings,
        StreamxClientFactory $streamxClientFactory,
        StreamxAvailabilityCheckerFactory $streamxAvailabilityCheckerFactory,
        IndexerRegistry $indexerRegistry,
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
            $indexerRegistry,
            $indexersConfig
        );
    }
}
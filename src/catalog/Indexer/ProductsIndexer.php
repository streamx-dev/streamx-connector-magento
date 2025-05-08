<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Indexer;

use Psr\Log\LoggerInterface;
use StreamX\ConnectorCatalog\Model\Indexer\DataLoader\ProductDataLoader;
use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCore\Api\IndexersConfigInterface;
use StreamX\ConnectorCore\Client\RabbitMQ\RabbitMqConfiguration;
use StreamX\ConnectorCore\Client\StreamxAvailabilityChecker;
use StreamX\ConnectorCore\Client\StreamxClient;
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
        StreamxClient $streamxClient,
        StreamxAvailabilityChecker $streamxAvailabilityChecker,
        RabbitMqConfiguration $rabbitMqConfiguration,
        IndexersConfigInterface $indexersConfig
    ) {
        parent::__construct(
            $connectorConfig,
            $indexedStoresProvider,
            $dataLoader,
            $logger,
            $optimizationSettings,
            $streamxClient,
            $streamxAvailabilityChecker,
            $rabbitMqConfiguration,
            $indexersConfig->getById(ProductProcessor::INDEXER_ID)
        );
    }
}
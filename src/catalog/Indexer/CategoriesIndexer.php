<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Indexer;

use Psr\Log\LoggerInterface;
use StreamX\ConnectorCatalog\Model\Indexer\Action\CategoryAction;
use StreamX\ConnectorCatalog\Model\Indexer\CategoryProcessor;
use StreamX\ConnectorCore\Api\IndexersConfigInterface;
use StreamX\ConnectorCore\Config\OptimizationSettings;
use StreamX\ConnectorCore\Indexer\BaseStreamxIndexer;
use StreamX\ConnectorCore\Indexer\IndexableStoresProvider;
use StreamX\ConnectorCore\Client\StreamxClientProvider;
use StreamX\ConnectorCore\System\GeneralConfig;

class CategoriesIndexer extends BaseStreamxIndexer
{
    public function __construct(
        GeneralConfig $connectorConfig,
        IndexableStoresProvider $indexableStoresProvider,
        CategoryAction $categoryAction,
        LoggerInterface $logger,
        OptimizationSettings $optimizationSettings,
        StreamxClientProvider $clientProvider,
        IndexersConfigInterface $indexersConfig
    ) {
        parent::__construct(
            $connectorConfig,
            $indexableStoresProvider,
            $categoryAction,
            $logger,
            $optimizationSettings,
            $clientProvider,
            $indexersConfig->getByName(CategoryProcessor::INDEXER_ID)
        );
    }
}
<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Indexer;

use Psr\Log\LoggerInterface;
use StreamX\ConnectorCatalog\Indexer\ProductIndexerHandler;
use StreamX\ConnectorCatalog\Model\Indexer\Action\Product as ProductAction;
use StreamX\ConnectorCore\Config\OptimizationSettings;
use StreamX\ConnectorCore\Indexer\IndexableStoresProvider;
use StreamX\ConnectorCore\Streamx\ClientResolver;
use StreamX\ConnectorCore\System\GeneralConfig;

class Product extends BaseStreamxIndexer {

    public function __construct(
        GeneralConfig $connectorConfig,
        ProductIndexerHandler $indexerHandler,
        IndexableStoresProvider $indexableStoresProvider,
        ProductAction $action,
        LoggerInterface $logger,
        OptimizationSettings $optimizationSettings,
        ClientResolver $clientResolver
    ) {
        parent::__construct(
            $connectorConfig,
            $indexerHandler,
            $indexableStoresProvider,
            $action,
            $logger,
            $optimizationSettings,
            $clientResolver,
            'Products'
        );
    }
}

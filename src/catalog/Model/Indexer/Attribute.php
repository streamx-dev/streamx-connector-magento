<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Indexer;

use Psr\Log\LoggerInterface;
use StreamX\ConnectorCatalog\Indexer\AttributeIndexerHandler;
use StreamX\ConnectorCatalog\Model\Indexer\Action\Attribute as AttributeAction;
use StreamX\ConnectorCore\Config\OptimizationSettings;
use StreamX\ConnectorCore\Indexer\IndexableStoresProvider;
use StreamX\ConnectorCore\Streamx\ClientResolver;
use StreamX\ConnectorCore\System\GeneralConfig;

class Attribute extends BaseStreamxIndexer {

    public function __construct(
        GeneralConfig $connectorConfig,
        AttributeIndexerHandler $indexerHandler,
        IndexableStoresProvider $indexableStoresProvider,
        AttributeAction $action,
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
            'Attributes'
        );
    }
}

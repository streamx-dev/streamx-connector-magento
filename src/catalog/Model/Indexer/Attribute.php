<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Indexer;

use Psr\Log\LoggerInterface;
use StreamX\ConnectorCatalog\Indexer\AttributeIndexerHandler;
use StreamX\ConnectorCatalog\Model\Indexer\Action\Attribute as AttributeAction;
use StreamX\ConnectorCore\Indexer\StoreManager;
use StreamX\ConnectorCore\System\GeneralConfigInterface;

class Attribute extends BaseStreamxIndexer {

    public function __construct(
        GeneralConfigInterface $connectorConfig,
        AttributeIndexerHandler $indexerHandler,
        StoreManager $storeManager,
        AttributeAction $action,
        LoggerInterface $logger
    ) {
        parent::__construct(
            $connectorConfig,
            $indexerHandler,
            $storeManager,
            $action,
            $logger,
            'Attributes'
        );
    }
}

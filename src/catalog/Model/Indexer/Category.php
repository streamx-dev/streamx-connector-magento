<?php

namespace StreamX\ConnectorCatalog\Model\Indexer;

use Psr\Log\LoggerInterface;
use StreamX\ConnectorCatalog\Model\Indexer\Action\Category as CategoryAction;
use StreamX\ConnectorCore\Indexer\GenericIndexerHandler;
use StreamX\ConnectorCore\Indexer\StoreManager;
use StreamX\ConnectorCore\System\GeneralConfigInterface;

class Category extends BaseStreamxIndexer {

    public function __construct(
        GeneralConfigInterface $connectorConfig,
        GenericIndexerHandler $indexerHandler,
        StoreManager $storeManager,
        CategoryAction $action,
        LoggerInterface $logger
    ) {
        parent::__construct(
            $connectorConfig,
            $indexerHandler,
            $storeManager,
            $action,
            $logger,
            'Categories'
        );
    }
}

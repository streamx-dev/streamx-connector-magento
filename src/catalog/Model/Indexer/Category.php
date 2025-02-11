<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Indexer;

use Psr\Log\LoggerInterface;
use StreamX\ConnectorCatalog\Indexer\CategoryIndexerHandler;
use StreamX\ConnectorCatalog\Model\Indexer\Action\Category as CategoryAction;
use StreamX\ConnectorCore\Indexer\StoreManager;
use StreamX\ConnectorCore\System\GeneralConfig;

class Category extends BaseStreamxIndexer {

    public function __construct(
        GeneralConfig $connectorConfig,
        CategoryIndexerHandler $indexerHandler,
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

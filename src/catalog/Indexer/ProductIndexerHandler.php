<?php

namespace StreamX\ConnectorCatalog\Indexer;

use Psr\Log\LoggerInterface;
use StreamX\ConnectorCore\Api\IndexersConfigInterface;
use StreamX\ConnectorCore\Api\IndexOperationInterface;
use StreamX\ConnectorCore\Indexer\GenericIndexerHandler;

class ProductIndexerHandler extends GenericIndexerHandler
{
    public function __construct(
        IndexOperationInterface $indexOperationProvider,
        LoggerInterface $logger,
        IndexersConfigInterface $indexersConfig
    ) {
        parent::__construct(
            $indexOperationProvider,
            $logger,
            $indexersConfig->getByName('product')
        );
    }

}
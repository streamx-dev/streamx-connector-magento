<?php

namespace StreamX\ConnectorCatalog\Indexer;

use Magento\Framework\Indexer\SaveHandler\Batch;
use Psr\Log\LoggerInterface;
use StreamX\ConnectorCore\Api\IndexersConfigInterface;
use StreamX\ConnectorCore\Api\IndexOperationInterface;
use StreamX\ConnectorCore\Indexer\GenericIndexerHandler;

class ProductIndexerHandler extends GenericIndexerHandler
{
    public function __construct(
        IndexOperationInterface $indexOperationProvider,
        LoggerInterface $logger,
        Batch $batch,
        IndexersConfigInterface $indexersConfig
    ) {
        parent::__construct(
            $indexOperationProvider,
            $logger,
            $batch,
            $indexersConfig->getByName('product')
        );
    }

}
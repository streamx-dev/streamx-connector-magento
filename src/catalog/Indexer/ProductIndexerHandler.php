<?php

namespace StreamX\ConnectorCatalog\Indexer;

use Psr\Log\LoggerInterface;
use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCore\Api\IndexersConfigInterface;
use StreamX\ConnectorCore\Index\IndexOperations;
use StreamX\ConnectorCore\Indexer\GenericIndexerHandler;

class ProductIndexerHandler extends GenericIndexerHandler
{
    // TODO: don't publish variant product when it is modified.
    //  Instead, load its parent simple_product and send it (along with all variants)

    public function __construct(
        IndexOperations $indexOperations,
        LoggerInterface $logger,
        IndexersConfigInterface $indexersConfig
    ) {
        parent::__construct(
            $indexOperations,
            $logger,
            $indexersConfig->getByName(ProductProcessor::INDEXER_ID)
        );
    }

}
<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Indexer;

use Psr\Log\LoggerInterface;
use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCore\Api\IndexersConfigInterface;
use StreamX\ConnectorCore\Index\IndexOperations;
use StreamX\ConnectorCore\Indexer\GenericIndexerHandler;

class ProductIndexerHandler extends GenericIndexerHandler
{
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
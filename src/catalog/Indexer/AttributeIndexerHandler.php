<?php

namespace StreamX\ConnectorCatalog\Indexer;

use Psr\Log\LoggerInterface;
use StreamX\ConnectorCatalog\Model\Indexer\AttributeProcessor;
use StreamX\ConnectorCore\Api\IndexersConfigInterface;
use StreamX\ConnectorCore\Index\IndexOperations;
use StreamX\ConnectorCore\Indexer\GenericIndexerHandler;

class AttributeIndexerHandler extends GenericIndexerHandler
{
    public function __construct(
        IndexOperations $indexOperations,
        LoggerInterface $logger,
        IndexersConfigInterface $indexersConfig
    ) {
        parent::__construct(
            $indexOperations,
            $logger,
            $indexersConfig->getByName(AttributeProcessor::INDEXER_ID)
        );
    }

}
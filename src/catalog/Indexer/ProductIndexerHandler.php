<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Indexer;

use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCore\Api\IndexersConfigInterface;
use StreamX\ConnectorCore\Config\OptimizationSettings;
use StreamX\ConnectorCore\Indexer\GenericIndexerHandler;

class ProductIndexerHandler extends GenericIndexerHandler
{
    public function __construct(
        OptimizationSettings $optimizationSettings,
        IndexersConfigInterface $indexersConfig
    ) {
        parent::__construct(
            $optimizationSettings,
            $indexersConfig->getByName(ProductProcessor::INDEXER_ID)
        );
    }
}
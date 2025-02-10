<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Indexer;

use StreamX\ConnectorCatalog\Model\Indexer\CategoryProcessor;
use StreamX\ConnectorCore\Api\IndexersConfigInterface;
use StreamX\ConnectorCore\Config\OptimizationSettings;
use StreamX\ConnectorCore\Indexer\GenericIndexerHandler;

class CategoryIndexerHandler extends GenericIndexerHandler
{
    public function __construct(
        OptimizationSettings $optimizationSettings,
        IndexersConfigInterface $indexersConfig
    ) {
        parent::__construct(
            $optimizationSettings,
            $indexersConfig->getByName(CategoryProcessor::INDEXER_ID)
        );
    }
}
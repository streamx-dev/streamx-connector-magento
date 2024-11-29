<?php

namespace StreamX\ConnectorCatalog\Console\Command;

use StreamX\ConnectorCatalog\Model\Indexer\ProductCategoryProcessor;

/**
 * @inheritDoc
 */
class IndexerInfoCommand extends \Magento\Indexer\Console\Command\IndexerInfoCommand
{
    /**
     * @inheritdoc
     */
    protected function getAllIndexers()
    {
        $indexers = parent::getAllIndexers();
        unset($indexers[ProductCategoryProcessor::INDEXER_ID]);

        return $indexers;
    }
}

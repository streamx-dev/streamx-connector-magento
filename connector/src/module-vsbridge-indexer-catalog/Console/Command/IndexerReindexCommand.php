<?php

namespace Divante\VsbridgeIndexerCatalog\Console\Command;

use Divante\VsbridgeIndexerCatalog\Model\Indexer\ProductCategoryProcessor;
use Symfony\Component\Console\Input\InputInterface;

class IndexerReindexCommand extends \Magento\Indexer\Console\Command\IndexerReindexCommand
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

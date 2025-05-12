<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Indexer;

use StreamX\ConnectorCatalog\Model\Indexer\DataLoader\CategoryDataLoader;
use StreamX\ConnectorCore\Indexer\BaseStreamxIndexer;
use StreamX\ConnectorCore\Indexer\StreamxIndexerServices;

class CategoryIndexer extends BaseStreamxIndexer {

    public const INDEXER_ID = 'streamx_category_indexer';

    public function __construct(StreamxIndexerServices $indexerServices, CategoryDataLoader $dataLoader) {
        parent::__construct($indexerServices, $dataLoader);
    }
}
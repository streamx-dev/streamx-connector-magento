<?php

namespace StreamX\ConnectorTestTools\Impl;

use Exception;
use StreamX\ConnectorCatalog\Cron\MView\StreamxIndexerMviewTrigger;
use StreamX\ConnectorTestTools\Api\MviewReindexerInterface;

class MviewReindexerImpl implements MviewReindexerInterface {

    private array $streamxIndexers = [];

    public function __construct(
        StreamxIndexerMviewTrigger $productIndexer,
        StreamxIndexerMviewTrigger $categoryIndexer,
        StreamxIndexerMviewTrigger $attributeIndexer
    ) {
        $this->streamxIndexers[$productIndexer->getIndexerViewId()] = $productIndexer;
        $this->streamxIndexers[$categoryIndexer->getIndexerViewId()] = $categoryIndexer;
        $this->streamxIndexers[$attributeIndexer->getIndexerViewId()] = $attributeIndexer;
    }

    /**
     * @throws Exception
     */
    public function reindexMview(string $indexerViewId): void {
        $this->streamxIndexers[$indexerViewId]->reindexMview();
    }
}
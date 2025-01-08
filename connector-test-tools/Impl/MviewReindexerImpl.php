<?php

namespace StreamX\ConnectorTestTools\Impl;

use Exception;
use StreamX\ConnectorCatalog\Cron\MView\StreamxIndexerMviewProcessor;
use StreamX\ConnectorTestTools\Api\MviewReindexerInterface;

class MviewReindexerImpl implements MviewReindexerInterface {

    use CoverageMeasurementTraits;

    private StreamxIndexerMviewProcessor $streamxIndexerMviewProcessor;

    public function __construct(StreamxIndexerMviewProcessor $streamxIndexerMviewProcessor) {
        $this->streamxIndexerMviewProcessor = $streamxIndexerMviewProcessor;
    }

    /**
     * @throws Exception
     */
    public function reindexMview(string $indexerViewId): string {
        return $this->doWithCoverageMeasurement(function() use ($indexerViewId) {
            $this->streamxIndexerMviewProcessor->reindexMview($indexerViewId);
        });
    }
}
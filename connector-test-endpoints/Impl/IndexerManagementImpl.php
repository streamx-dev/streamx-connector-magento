<?php

namespace StreamX\ConnectorTestEndpoints\Impl;

use InvalidArgumentException;
use Magento\Framework\Indexer\IndexerRegistry;
use StreamX\ConnectorTestEndpoints\Api\IndexerManagementInterface;

class IndexerManagementImpl implements IndexerManagementInterface {

    private IndexerRegistry $indexerRegistry;

    public function __construct(IndexerRegistry $indexerRegistry) {
        $this->indexerRegistry = $indexerRegistry;
    }

    /**
     * @inheritdoc
     */
    public function getIndexerMode(string $indexerId): string {
        $indexer = $this->indexerRegistry->get($indexerId);
        $isScheduled = $indexer->isScheduled();
        return $isScheduled ? self::UPDATE_BY_SCHEDULE : self::UPDATE_ON_SAVE;
    }

    /**
     * @inheritdoc
     */
    public function setIndexerMode(string $indexerId, string $mode): void {
        self::validateMode($mode);
        $indexer = $this->indexerRegistry->get($indexerId);
        $setAsScheduled = $mode === self::UPDATE_BY_SCHEDULE;

        $shouldChangeMode = $indexer->isScheduled() !== $setAsScheduled;
        if ($shouldChangeMode) {
            $indexer->setScheduled($setAsScheduled);
        }
    }

    private static function validateMode(string $mode): void {
        if ($mode !== self::UPDATE_BY_SCHEDULE && $mode !== self::UPDATE_ON_SAVE) {
            throw new InvalidArgumentException("Invalid mode '$mode'");
        }
    }
}

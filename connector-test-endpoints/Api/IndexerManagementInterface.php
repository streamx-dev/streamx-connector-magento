<?php

namespace StreamX\ConnectorTestEndpoints\Api;

interface IndexerManagementInterface {

    const UPDATE_ON_SAVE = 'update-on-save';
    const UPDATE_BY_SCHEDULE = 'update-by-schedule';

    /**
     * Retrieves current mode of the indexer
     * @param string $indexerId Indexer ID, as printed by the "bin/magento indexer:status" command
     * @return string "update-on-save" or "update-by-schedule"
     */
    public function getIndexerMode(string $indexerId): string;

    /**
     * Switches the indexer to the requested mode
     * @param string $indexerId Indexer ID, as printed by the "bin/magento indexer:status" command
     * @param string $mode "update-on-save" or "update-by-schedule"
     * @return void
     */
    public function setIndexerMode(string $indexerId, string $mode): void;

    /**
     * Runs the given indexer, with the given entity IDs
     * @param string $indexerId Indexer ID, as printed by the "bin/magento indexer:status" command
     * @param int[] $entityIds IDs of products or categories or indexers, matching the provided indexer ID
     * @return void
     */
    public function runIndexer(string $indexerId, array $entityIds): void;
}

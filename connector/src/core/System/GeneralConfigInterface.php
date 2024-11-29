<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\System;

interface GeneralConfigInterface
{
    /**
     * Indexer enabled config path
     */
    const XML_PATH_GENERAL_INDEXER_ENABLED = 'streamx_indexer_settings/general_settings/enable';

    /**
     * Allowed stores to reindex config path
     */
    const XML_PATH_ALLOWED_STORES_TO_REINDEX = 'streamx_indexer_settings/general_settings/allowed_stores';

    /**
     * Check if store can be reindex
     */
    public function canReindexStore(int $storeId): bool;

    /**
     * Get Store ids allowed to reindex
     */
    public function getStoresToIndex(): array;

    /**
     * Check if ES indexing enabled
     */
    public function isEnabled(): bool;
}

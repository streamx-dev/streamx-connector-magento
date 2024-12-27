<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\System;

interface GeneralConfigInterface
{
    /**
     * Connector enabled config path
     */
    const XML_PATH_GENERAL_CONNECTOR_ENABLED = 'streamx_connector_settings/general_settings/enable';

    /**
     * Allowed stores to reindex config path
     */
    const XML_PATH_ALLOWED_STORES_TO_REINDEX = 'streamx_connector_settings/general_settings/allowed_stores';

    /**
     * Check if store can be reindex
     */
    public function canReindexStore(int $storeId): bool;

    /**
     * Get Store ids allowed to reindex
     */
    public function getStoresToIndex(): array;

    /**
     * Check if StreamX Connector is enabled
     */
    public function isEnabled(): bool;
}

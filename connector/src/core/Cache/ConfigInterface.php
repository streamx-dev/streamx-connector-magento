<?php

declare(strict_types=1);

namespace StreamX\ConnectorCore\Cache;

interface ConfigInterface
{
    /**
     * XML PATH Prefix for redis cache settings
     */
    const CACHE_SETTINGS_XML_PREFIX = 'streamx_indexer_settings/redis_cache_settings';

    const CLEAR_CACHE_FIELD = 'clear_cache';

    public function clearCache(int $storeId): bool;
}

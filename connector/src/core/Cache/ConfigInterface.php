<?php

declare(strict_types=1);

namespace Divante\VsbridgeIndexerCore\Cache;

interface ConfigInterface
{
    /**
     * XML PATH Prefix for redis cache settings
     */
    const CACHE_SETTINGS_XML_PREFIX = 'vsbridge_indexer_settings/redis_cache_settings';

    const CLEAR_CACHE_FIELD = 'clear_cache';
    const VSF_BASE_URL_FIELD = 'vsf_base_url';
    const INVALIDATE_CACHE_FIELD = 'invalidate_cache_key';
    const CONNECTION_TIMEOUT_FIELD = 'connection_timeout';

    const INVALIDATE_CACHE_ENTITIES_BATCH_SIZE_FIELD = 'entity_invalidate_batch_size';

    public function clearCache(int $storeId): bool;

    public function getInvalidateEntitiesBatchSize(int $storeId): int;

    public function getVsfBaseUrl(int $storeId): string;

    public function getInvalidateCacheKey(int $storeId): string;

    public function getTimeout(int $storeId): int;

    public function getConnectionOptions(int $storeId): array;
}

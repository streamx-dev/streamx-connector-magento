<?php

namespace Divante\VsbridgeIndexerCore\Index\Indicies\Config;

use Magento\Framework\Config\CacheInterface;
use Magento\Framework\Config\Data as DataConfig;
use Magento\Framework\Serialize\SerializerInterface;

class Data extends DataConfig
{
    const CACHE_ID = 'vsf_indices_config';

    public function __construct(
        Reader $reader,
        CacheInterface $cache,
        string $cacheId = self::CACHE_ID,
        SerializerInterface $serializer = null
    ) {
        parent::__construct($reader, $cache, $cacheId, $serializer);
    }
}

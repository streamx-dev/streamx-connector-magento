<?php

namespace Divante\VsbridgeIndexerCore\Api\Indexer;

interface TransactionKeyInterface
{
    /**
     * @return int|string
     */
    public function load();
}

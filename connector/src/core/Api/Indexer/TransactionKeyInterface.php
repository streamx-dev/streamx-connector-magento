<?php

namespace StreamX\ConnectorCore\Api\Indexer;

interface TransactionKeyInterface
{
    /**
     * @return int|string
     */
    public function load();
}

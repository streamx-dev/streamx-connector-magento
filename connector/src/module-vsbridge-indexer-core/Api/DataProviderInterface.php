<?php

namespace Divante\VsbridgeIndexerCore\Api;

interface DataProviderInterface
{
    /**
     * Append data to a list of documents.
     *
     * @param array $indexData
     * @param int $storeId
     *
     * @return array
     */
    public function addData(array $indexData, $storeId);
}

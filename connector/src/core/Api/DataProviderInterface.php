<?php

namespace StreamX\ConnectorCore\Api;

interface DataProviderInterface
{
    /**
     * Append data to a list of documents.
     */
    public function addData(array $indexData, int $storeId): array;
}

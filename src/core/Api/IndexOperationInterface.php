<?php

namespace StreamX\ConnectorCore\Api;

use StreamX\ConnectorCore\Index\BulkRequest;

interface IndexOperationInterface
{
    public function executeBulk(int $storeId, BulkRequest $bulk): void;

    public function getBatchIndexingSize(): int;
}

<?php

namespace StreamX\ConnectorCore\Api;

use StreamX\ConnectorCore\Index\BulkRequest;
use StreamX\ConnectorCore\Index\BulkResponse;

interface IndexOperationInterface
{
    public function executeBulk(int $storeId, BulkRequest $bulk): BulkResponse;

    public function getBatchIndexingSize(): int;
}

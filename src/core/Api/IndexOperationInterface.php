<?php

namespace StreamX\ConnectorCore\Api;

use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use StreamX\ConnectorCore\Exception\ConnectionUnhealthyException;
use StreamX\ConnectorCore\Index\BulkRequest;

interface IndexOperationInterface
{
    /**
     * @throws ConnectionUnhealthyException
     * @throws StreamxClientException
     */
    public function executeBulk(int $storeId, BulkRequest $bulk): void;

    public function getBatchIndexingSize(): int;
}

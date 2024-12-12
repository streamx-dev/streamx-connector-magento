<?php

namespace StreamX\ConnectorCore\Api;

interface IndexOperationInterface
{
    public function executeBulk(int $storeId, BulkRequestInterface $bulk): BulkResponseInterface;

    public function createBulk(): BulkRequestInterface;

    public function getBatchIndexingSize(): int;
}

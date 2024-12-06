<?php

namespace StreamX\ConnectorCore\Api;

interface IndexOperationInterface
{
    public function executeBulk(int $storeId, BulkRequestInterface $bulk): BulkResponseInterface;

    public function deleteByQuery(int $storeId, array $params): void;

    public function createBulk(): BulkRequestInterface;

    public function getBatchIndexingSize(): int;
}

<?php

namespace StreamX\ConnectorCore\Api;

use Magento\Store\Api\Data\StoreInterface;

interface IndexOperationInterface
{
    public function executeBulk(int $storeId, BulkRequestInterface $bulk): BulkResponseInterface;

    public function deleteByQuery(int $storeId, array $params): void;

    public function getIndexByName(string $indexIdentifier, StoreInterface $store): IndexInterface;

    public function createBulk(): BulkRequestInterface;

    public function getBatchIndexingSize(): int;
}

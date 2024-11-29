<?php

namespace Divante\VsbridgeIndexerCore\Api;

use Magento\Store\Api\Data\StoreInterface;

interface IndexOperationInterface
{
    public function executeBulk(int $storeId, BulkRequestInterface $bulk): BulkResponseInterface;

    public function deleteByQuery(int $storeId, array $params): void;

    public function indexExists(int $storeId, string $indexName): bool;

    public function getIndexByName(string $indexIdentifier, StoreInterface $store): IndexInterface;

    public function getIndexAlias(StoreInterface $store): string;

    public function createIndex(string $indexIdentifier, StoreInterface $store): IndexInterface;

    public function refreshIndex(int $storeId, IndexInterface $index): void;

    public function switchIndexer(int $storeId, string $indexName, string $indexAlias): void;

    public function createBulk(): BulkRequestInterface;

    public function getBatchIndexingSize(): int;

    public function optimizeEsIndexing(int $storeId, string $indexName): void;

    public function cleanAfterOptimizeEsIndexing(int $storeId, string $indexName): void;
}

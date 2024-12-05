<?php

namespace StreamX\ConnectorCore\Api\Client;

// TODO remove methods not required by StreamX indexer
interface ClientInterface {

    public function bulk(array $bulkParams): array;

    public function createIndex(string $indexName, array $indexSettings): void;

    /**
     * Retrieve information about cluster health
     */
    public function getClustersHealth(): array;

    /**
     * Retrieve max queue size for master node
     */
    public function getMasterMaxQueueSize(): int;

    public function indexExists(string $indexName): bool;

    public function putMapping(string $indexName, string $type, array $mapping);

    public function deleteByQuery(array $params);
}

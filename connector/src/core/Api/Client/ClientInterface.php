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
     * Retrieve the list of all index having a specified alias.
     */
    public function getIndicesNameByAlias(string $indexAlias): array;

    /**
     * Retrieve information about index settings
     */
    public function getIndexSettings(string $indexName): array;

    /**
     * Retrieve max queue size for master node
     */
    public function getMasterMaxQueueSize(): int;

    public function updateAliases(array $aliasActions): void;

    public function refreshIndex(string $indexName): void;

    public function indexExists(string $indexName): bool;

    public function deleteIndex(string $indexName): array;

    public function putMapping(string $indexName, string $type, array $mapping);

    public function deleteByQuery(array $params);
}

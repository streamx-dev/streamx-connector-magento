<?php

namespace StreamX\ConnectorCore\Api\Client;

// TODO remove methods not required by StreamX indexer
interface ClientInterface {

    public function bulk(array $bulkParams): array;

    /**
     * Retrieve information about cluster health
     */
    public function getClustersHealth(): array;

    /**
     * Retrieve max queue size for master node
     */
    public function getMasterMaxQueueSize(): int;

    public function deleteByQuery(array $params);
}

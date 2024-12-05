<?php

namespace StreamX\ConnectorCore\Api\Client;

// TODO remove methods not required by StreamX indexer
interface ClientInterface {

    public function bulk(array $bulkParams): array;

    /**
     * Retrieve information about cluster health
     */
    public function getClustersHealth(): array;

    public function deleteByQuery(array $params);
}

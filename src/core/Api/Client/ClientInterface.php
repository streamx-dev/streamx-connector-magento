<?php

namespace StreamX\ConnectorCore\Api\Client;

interface ClientInterface {

    public function ingest(array $bulkOperations): void;

    /**
     * Retrieve information about cluster health
     */
    public function getClustersHealth(): array;
}

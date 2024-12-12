<?php

namespace StreamX\ConnectorCore\Api\Client;

interface ClientInterface {

    public function bulk(array $bulkParams): array;

    /**
     * Retrieve information about cluster health
     */
    public function getClustersHealth(): array;
}

<?php

namespace StreamX\ConnectorCore\Api\Client;

interface ClientInterface {

    public function ingest(array $bulkOperations): void;

    public function isStreamxAvailable(): bool;
}

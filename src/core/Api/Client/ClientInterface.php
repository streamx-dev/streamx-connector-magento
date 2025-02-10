<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Api\Client;

interface ClientInterface {

    public function ingest(array $bulkOperations): void;

    public function isStreamxAvailable(): bool;
}

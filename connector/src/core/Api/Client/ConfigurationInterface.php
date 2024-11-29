<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Api\Client;

interface ConfigurationInterface {
    public function getIngestionBaseUrl(int $storeId): string;

    public function getPagesSchemaName(int $storeId): string;

    public function getOptions(int $storeId): array;
}

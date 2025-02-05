<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Api\Client;

interface ConfigurationInterface {
    public function getIngestionBaseUrl(int $storeId): string;

    public function getChannelName(int $storeId): string;

    public function getChannelSchemaName(int $storeId): string;

    public function getProductKeyPrefix(int $storeId): string;

    public function getCategoryKeyPrefix(int $storeId): string;

    public function getAuthToken(int $storeId): ?string;

    public function shouldDisableCertificateValidation(int $storeId): bool;
}

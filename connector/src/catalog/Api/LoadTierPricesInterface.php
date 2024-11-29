<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Api;

interface LoadTierPricesInterface
{
    public function execute(array $indexData, int $storeId): array;
}

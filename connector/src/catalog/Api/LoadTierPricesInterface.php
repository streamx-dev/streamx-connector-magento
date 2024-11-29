<?php declare(strict_types=1);

namespace Divante\VsbridgeIndexerCatalog\Api;

interface LoadTierPricesInterface
{
    public function execute(array $indexData, int $storeId): array;
}

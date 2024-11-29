<?php

declare(strict_types=1);

namespace Divante\VsbridgeIndexerCatalog\Api;

interface LoadInventoryInterface
{
    public function execute(array $indexData, int $storeId): array;
}

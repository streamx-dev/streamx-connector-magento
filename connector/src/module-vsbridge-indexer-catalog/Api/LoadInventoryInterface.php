<?php

declare(strict_types=1);

namespace Divante\VsbridgeIndexerCatalog\Api;

interface LoadInventoryInterface
{
    /**
     * @param array $indexData
     * @param int $storeId
     *
     * @return array
     */
    public function execute(array $indexData, int $storeId): array;
}

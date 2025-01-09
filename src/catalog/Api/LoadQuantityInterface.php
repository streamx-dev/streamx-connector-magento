<?php

declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Api;

interface LoadQuantityInterface
{
    public function execute(array $indexData, int $storeId): array;
}

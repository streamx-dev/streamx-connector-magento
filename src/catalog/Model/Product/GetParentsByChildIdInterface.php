<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Product;

interface GetParentsByChildIdInterface
{
    /**
     * Retrieve parent sku array by requested children
     */
    public function execute(array $childId): array;
}

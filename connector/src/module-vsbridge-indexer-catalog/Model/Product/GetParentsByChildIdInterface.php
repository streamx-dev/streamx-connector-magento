<?php declare(strict_types=1);

namespace Divante\VsbridgeIndexerCatalog\Model\Product;

interface GetParentsByChildIdInterface
{
    /**
     * Retrieve parent sku array by requested children
     *
     * @param array $childId
     *
     * @return array
     */
    public function execute(array $childId): array;
}

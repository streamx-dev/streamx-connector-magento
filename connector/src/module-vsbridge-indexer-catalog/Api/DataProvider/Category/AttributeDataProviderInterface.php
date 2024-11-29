<?php

declare(strict_types=1);

namespace Divante\VsbridgeIndexerCatalog\Api\DataProvider\Category;

use Divante\VsbridgeIndexerCore\Api\DataProviderInterface;

interface AttributeDataProviderInterface extends DataProviderInterface
{
    public function prepareParentCategory(array $categoryDTO, int $storeId): array;

    public function prepareChildCategory(array $categoryDTO, int $storeId): array;
}

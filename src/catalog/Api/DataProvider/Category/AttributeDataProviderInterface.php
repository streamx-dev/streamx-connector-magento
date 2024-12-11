<?php

declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Api\DataProvider\Category;

use StreamX\ConnectorCore\Api\DataProviderInterface;

interface AttributeDataProviderInterface extends DataProviderInterface
{
    public function prepareParentCategory(array $categoryDTO, int $storeId): array;

    public function prepareChildCategory(array $categoryDTO, int $storeId): array;
}

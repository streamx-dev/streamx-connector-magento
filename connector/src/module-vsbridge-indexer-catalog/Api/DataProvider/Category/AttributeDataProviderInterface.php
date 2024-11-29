<?php

declare(strict_types=1);

namespace Divante\VsbridgeIndexerCatalog\Api\DataProvider\Category;

use Divante\VsbridgeIndexerCore\Api\DataProviderInterface;

interface AttributeDataProviderInterface extends DataProviderInterface
{
    /**
     * @param array $categoryDTO
     * @param int $storeId
     *
     * @return array
     */
    public function prepareParentCategory(array $categoryDTO, int $storeId);

    /**
     * @param array $categoryDTO
     * @param int $storeId
     *
     * @return array
     */
    public function prepareChildCategory(array $categoryDTO, int $storeId);
}

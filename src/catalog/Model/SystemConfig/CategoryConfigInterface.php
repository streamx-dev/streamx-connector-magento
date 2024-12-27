<?php

namespace StreamX\ConnectorCatalog\Model\SystemConfig;

use Exception;

/**
 * @api
 */
interface CategoryConfigInterface
{
    /**
     * Retrieve Category Url Suffix
     */
    public function getCategoryUrlSuffix(int $storeId): string;

    /**
     * Retrieve attributes used for sort by
     *
     * @throws Exception
     */
    public function getAttributesUsedForSortBy(): array;

    /**
     * Retrieve default product attribute used for sort by
     */
    public function getProductListDefaultSortBy(int $storeId): string;
}

<?php

namespace Divante\VsbridgeIndexerCatalog\Api;

/**
 * Interface ApplyCategorySlugInterface
 */
interface ApplyCategorySlugInterface
{
    /**
     * @param array $category
     *
     * @return array
     */
    public function execute(array $category);
}

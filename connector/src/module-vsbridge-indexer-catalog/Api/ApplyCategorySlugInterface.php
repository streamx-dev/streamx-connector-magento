<?php

namespace Divante\VsbridgeIndexerCatalog\Api;

interface ApplyCategorySlugInterface
{
    /**
     * @param array $category
     *
     * @return array
     */
    public function execute(array $category);
}

<?php

namespace Divante\VsbridgeIndexerCatalog\Api;

interface ApplyCategorySlugInterface
{
    public function execute(array $category): array;
}

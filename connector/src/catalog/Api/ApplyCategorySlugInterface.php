<?php

namespace StreamX\ConnectorCatalog\Api;

interface ApplyCategorySlugInterface
{
    public function execute(array $category): array;
}

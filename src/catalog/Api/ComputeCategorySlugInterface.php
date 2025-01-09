<?php

namespace StreamX\ConnectorCatalog\Api;

interface ComputeCategorySlugInterface
{
    public function compute(array $category): string;
}

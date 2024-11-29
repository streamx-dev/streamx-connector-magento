<?php

namespace Divante\VsbridgeIndexerCatalog\Api;

interface SlugGeneratorInterface
{

    public function generate(string $text, int $id): string;
}

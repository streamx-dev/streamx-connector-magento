<?php

namespace StreamX\ConnectorCatalog\Api;

interface SlugGeneratorInterface
{

    public function generate(string $text, int $id): string;
}

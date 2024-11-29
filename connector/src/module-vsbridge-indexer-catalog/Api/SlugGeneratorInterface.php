<?php

namespace Divante\VsbridgeIndexerCatalog\Api;

interface SlugGeneratorInterface
{

    /**
     * @param string $text
     * @param int $id
     *
     * @return string
     */
    public function generate($text, $id);
}

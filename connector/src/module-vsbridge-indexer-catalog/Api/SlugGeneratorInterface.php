<?php

namespace Divante\VsbridgeIndexerCatalog\Api;

/**
 * Interface SlugGeneratorInterface
 */
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

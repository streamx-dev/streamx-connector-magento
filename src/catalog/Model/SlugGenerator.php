<?php

namespace StreamX\ConnectorCatalog\Model;

use StreamX\ConnectorCatalog\Api\SlugGeneratorInterface;

class SlugGenerator implements SlugGeneratorInterface
{
    public function generate(string $text, int $id): string
    {
        $text = $text . '-' . $id;

        return $this->slugify($text);
    }

    private function slugify(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace("/\s+/", '-', $text);// Replace spaces with -
        $text = preg_replace("/&/", '-and-', $text); //Replace & with 'and'
        $text = preg_replace("/[^\w-]+/", '', $text);// Remove all non-word chars
        $text = preg_replace("/--+/", '-', $text);// Replace multiple - with single -

        return $text;
    }
}

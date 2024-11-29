<?php

namespace Divante\VsbridgeIndexerCatalog\Model;

use Divante\VsbridgeIndexerCatalog\Api\SlugGeneratorInterface;

class SlugGenerator implements SlugGeneratorInterface
{
    /**
     * @inheritdoc
     */
    public function generate(string $text, int $id): string
    {
        $text = $text . '-' . $id;

        return $this->slugify($text);
    }

    /**
     * @return string
     */
    private function slugify(string $text)
    {
        $text = mb_strtolower($text);
        $text = preg_replace("/\s+/", '-', $text);// Replace spaces with -
        $text = preg_replace("/&/", '-and-', $text); //Replace & with 'and'
        $text = preg_replace("/[^\w-]+/", '', $text);// Remove all non-word chars
        $text = preg_replace("/--+/", '-', $text);// Replace multiple - with single -

        return $text;
    }
}

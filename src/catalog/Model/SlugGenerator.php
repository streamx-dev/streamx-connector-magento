<?php

namespace StreamX\ConnectorCatalog\Model;

class SlugGenerator
{
    public static function generate(string $text, int $id): string
    {
        return self::slugify("$text-$id");
    }

    private static function slugify(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace("/\s+/", '-', $text);// Replace spaces with -
        $text = preg_replace("/&/", '-and-', $text); //Replace & with 'and'
        $text = preg_replace("/[^\w-]+/", '', $text);// Remove all non-word chars
        $text = preg_replace("/--+/", '-', $text);// Replace multiple - with single -

        return $text;
    }
}

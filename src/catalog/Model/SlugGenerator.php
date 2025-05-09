<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model;

use StreamX\ConnectorCatalog\Model\Config\Source\SlugOptionsSource;
use StreamX\ConnectorCatalog\Model\SystemConfig\CatalogConfig;

class SlugGenerator
{
    private CatalogConfig $settings;

    public function __construct(CatalogConfig $configSettings)
    {
        $this->settings = $configSettings;
    }

    public function compute(int $id, string $name, ?string $urlKey): string
    {
        $slugGenerationStrategy = $this->settings->slugGenerationStrategy();
        if (empty($urlKey)) {
            $slugGenerationStrategy = SlugOptionsSource::NAME_AND_ID;
        }

        switch ($slugGenerationStrategy) {
            case SlugOptionsSource::URL_KEY:
                return $urlKey;
            case SlugOptionsSource::URL_KEY_AND_ID:
                return "$urlKey-$id";
            default:
                return self::slugify("$name-$id");
        }
    }

    public static function slugify(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace("/\s+/", '-', $text);// Replace spaces with -
        $text = preg_replace("/&/", '-and-', $text); //Replace & with 'and'
        $text = preg_replace("/[^\w-]+/", '', $text);// Remove all non-word chars
        $text = preg_replace("/--+/", '-', $text);// Replace multiple - with single -

        return $text;
    }
}

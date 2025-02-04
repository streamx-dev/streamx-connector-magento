<?php

namespace StreamX\ConnectorCatalog\Model;

use StreamX\ConnectorCatalog\Model\SystemConfig\CatalogConfig;

class SlugGenerator
{
    private CatalogConfig $settings;

    public function __construct(CatalogConfig $configSettings)
    {
        $this->settings = $configSettings;
    }

    /**
     * @param array $entity Product or Category. Must contain 'id' and 'name' fields, may contain 'url_key' field
     * @return string
     */
    public function compute(array $entity): string
    {
        $id = $entity['id'];
        $name = $entity['name'];
        $urlKey = $entity['url_key'] ?? '';

        if ($this->settings->useUrlKeyToGenerateSlug() && !empty($urlKey)) {
            return $urlKey;
        }

        if ($this->settings->useUrlKeyAndIdToGenerateSlug() && !empty($urlKey)) {
            return self::slugify("$urlKey-$id");
        }

        return self::slugify("$name-$id");
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

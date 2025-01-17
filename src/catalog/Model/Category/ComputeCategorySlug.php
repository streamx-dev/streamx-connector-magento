<?php

namespace StreamX\ConnectorCatalog\Model\Category;

use StreamX\ConnectorCatalog\Model\SystemConfig\CatalogConfig;
use StreamX\ConnectorCatalog\Model\SlugGenerator;

class ComputeCategorySlug
{
    private CatalogConfig $settings;

    public function __construct(CatalogConfig $configSettings)
    {
        $this->settings = $configSettings;
    }

    public function compute(array $category): string
    {
        if ($this->settings->useMagentoUrlKeys()) {
            return $category['url_key'] ?? SlugGenerator::generate(
                $category['name'],
                $category['entity_id']
            );
        }

        $text = $this->settings->useUrlKeyToGenerateSlug() && isset($category['url_key'])
            ? $category['url_key']
            : $category['name'];

        return SlugGenerator::generate($text, $category['entity_id']);
    }
}

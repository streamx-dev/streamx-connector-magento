<?php

namespace StreamX\ConnectorCatalog\Model\Category;

use StreamX\ConnectorCatalog\Api\ComputeCategorySlugInterface;
use StreamX\ConnectorCatalog\Api\CatalogConfigurationInterface;
use StreamX\ConnectorCatalog\Model\SlugGenerator;

class ComputeCategorySlug implements ComputeCategorySlugInterface
{
    private CatalogConfigurationInterface $settings;

    public function __construct(CatalogConfigurationInterface $configSettings)
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

<?php

namespace StreamX\ConnectorCatalog\Model\Category;

use StreamX\ConnectorCatalog\Api\ApplyCategorySlugInterface;
use StreamX\ConnectorCatalog\Api\CatalogConfigurationInterface;
use StreamX\ConnectorCatalog\Api\SlugGeneratorInterface;

class ApplyCategorySlug implements ApplyCategorySlugInterface
{
    private CatalogConfigurationInterface $settings;
    private SlugGeneratorInterface $slugGenerator;

    public function __construct(
        SlugGeneratorInterface $slugGenerator,
        CatalogConfigurationInterface $configSettings
    ) {
        $this->settings = $configSettings;
        $this->slugGenerator = $slugGenerator;
    }

    public function execute(array $category): array
    {
        if ($this->settings->useMagentoUrlKeys()) {
            if (!isset($category['url_key'])) {
                $slug = $this->slugGenerator->generate(
                    $category['name'],
                    $category['entity_id']
                );
                $category['url_key'] = $slug;
            }

            $category['slug'] = $category['url_key'];
        } else {
            $text = $category['name'];

            if ($this->settings->useUrlKeyToGenerateSlug() && isset($category['url_key'])) {
                $text = $category['url_key'];
            }

            $slug = $this->slugGenerator->generate($text, $category['entity_id']);
            $category['url_key'] = $slug;
            $category['slug'] = $slug;
        }

        return $category;
    }
}

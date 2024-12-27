<?php

namespace StreamX\ConnectorCatalog\Model\Attributes;

use StreamX\ConnectorCatalog\Api\CatalogConfigurationInterface;

class ProductAttributes
{
    const REQUIRED_ATTRIBUTES = [
        'sku',
        'url_path',
        'url_key',
        'name',
        'price',
        'visibility',
        'status',
        'price_type',
    ];

    private CatalogConfigurationInterface $catalogConfig;

    public function __construct(CatalogConfigurationInterface $catalogConfiguration)
    {
        $this->catalogConfig = $catalogConfiguration;
    }

    public function getAttributes(int $storeId): array
    {
        $attributes = $this->catalogConfig->getAllowedAttributesToIndex($storeId);

        if (empty($attributes)) {
            return [];
        }

        return array_merge($attributes, self::REQUIRED_ATTRIBUTES);
    }
}

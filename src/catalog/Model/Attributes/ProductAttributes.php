<?php

namespace StreamX\ConnectorCatalog\Model\Attributes;

use StreamX\ConnectorCatalog\Model\SystemConfig\CatalogConfig;

class ProductAttributes extends BaseProductAttributes
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

    public function __construct(CatalogConfig $catalogConfiguration)
    {
        parent::__construct($catalogConfiguration, self::REQUIRED_ATTRIBUTES);
    }

    protected function getConfiguredAttributes(int $storeId): array
    {
        return $this->catalogConfig->getAttributesToIndex($storeId);
    }
}

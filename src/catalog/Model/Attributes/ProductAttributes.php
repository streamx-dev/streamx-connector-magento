<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Attributes;

use StreamX\ConnectorCatalog\Model\SystemConfig\CatalogConfig;

class ProductAttributes extends BaseProductAttributes
{
    const ALWAYS_LOADED_ATTRIBUTES = [
        'name',
        'image',
        'description',
        'price',
        'url_key',
        'media_gallery'
    ];

    public function __construct(CatalogConfig $catalogConfiguration)
    {
        parent::__construct($catalogConfiguration, self::ALWAYS_LOADED_ATTRIBUTES);
    }

    protected function getConfiguredAttributes(int $storeId): array
    {
        return $this->catalogConfig->getProductAttributesToIndex($storeId);
    }
}

<?php

namespace StreamX\ConnectorCatalog\Model\Attributes;

use Magento\Framework\App\ResourceConnection;
use StreamX\ConnectorCatalog\Model\SystemConfig\CatalogConfig;
use StreamX\ConnectorCatalog\Model\Attribute\LoadOptions;

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

    public function __construct(
        CatalogConfig $catalogConfiguration,
        ResourceConnection $resource,
        LoadOptions $loadOptions
    ) {
        parent::__construct($catalogConfiguration, $resource,  $loadOptions);
    }

    protected function getRequiredAttributes(): array
    {
        return self::REQUIRED_ATTRIBUTES;
    }

    protected function getConfiguredAttributes(int $storeId): array
    {
        return $this->catalogConfig->getAttributesToIndex($storeId);
    }
}

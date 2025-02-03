<?php

namespace StreamX\ConnectorCatalog\Model\Attributes;

use StreamX\ConnectorCatalog\Model\SystemConfig\CatalogConfig;

class ChildProductAttributes extends BaseProductAttributes
{
    const MINIMAL_ATTRIBUTE_SET = [
        'sku',
        'name',
        'price',
    ];

    public function __construct(CatalogConfig $catalogConfiguration)
    {
        parent::__construct($catalogConfiguration, self::MINIMAL_ATTRIBUTE_SET);
    }

    protected function getConfiguredAttributes(int $storeId): array
    {
        return $this->catalogConfig->getChildAttributesToIndex($storeId);
    }
}

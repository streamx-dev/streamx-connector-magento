<?php

namespace StreamX\ConnectorCatalog\Model\Attributes;

use Magento\Framework\App\ResourceConnection;
use StreamX\ConnectorCatalog\Model\Attribute\LoadOptions;
use StreamX\ConnectorCatalog\Model\SystemConfig\CatalogConfig;

class ChildProductAttributes extends BaseProductAttributes
{

    /**
     * This product attributes always be exported for configurable_children
     */
    const MINIMAL_ATTRIBUTE_SET = [
        'sku',
        'name',
        'price',
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
        return self::MINIMAL_ATTRIBUTE_SET;
    }

    protected function getConfiguredAttributes(int $storeId): array
    {
        return $this->catalogConfig->getChildAttributesToIndex($storeId);
    }
}

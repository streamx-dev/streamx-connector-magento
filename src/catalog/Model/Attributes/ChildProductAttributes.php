<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Attributes;

use StreamX\ConnectorCatalog\Model\SystemConfig\CatalogConfig;

class ChildProductAttributes extends BaseProductAttributes
{
    public function __construct(CatalogConfig $catalogConfiguration)
    {
        parent::__construct($catalogConfiguration);
    }

    protected function getConfiguredAttributes(int $storeId): array
    {
        return $this->catalogConfig->getChildProductAttributesToIndex($storeId);
    }
}

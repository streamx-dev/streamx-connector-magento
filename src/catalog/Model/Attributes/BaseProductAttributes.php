<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Attributes;

use StreamX\ConnectorCatalog\Model\Config\Source\Product\BaseProductAttributeSource;
use StreamX\ConnectorCatalog\Model\SystemConfig\CatalogConfig;

abstract class BaseProductAttributes
{
    protected CatalogConfig $catalogConfig;

    public function __construct(CatalogConfig $catalogConfiguration) {
        $this->catalogConfig = $catalogConfiguration;
    }

    /**
     * @return string[] of attribute codes that are configured to be exported in the Connector settings
     */
    protected abstract function getConfiguredAttributes(int $storeId): array;

    /**
     * @return string[] attribute codes. If empty - it means the Connector should index all attributes
     */
    public function getAttributesToIndex(int $storeId): array
    {
        $attributeCodes = $this->getConfiguredAttributes($storeId);

        return empty($attributeCodes)
            ? []
            : array_merge($attributeCodes, BaseProductAttributeSource::ALWAYS_LOADED_ATTRIBUTES);
    }
}

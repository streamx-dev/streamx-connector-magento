<?php

namespace StreamX\ConnectorCatalog\Model\Attributes;

use StreamX\ConnectorCatalog\Model\SystemConfig\CatalogConfig;

abstract class BaseProductAttributes
{
    protected CatalogConfig $catalogConfig;
    private array $requiredAttributes;

    public function __construct(CatalogConfig $catalogConfiguration, array $requiredAttributes) {
        $this->catalogConfig = $catalogConfiguration;
        $this->requiredAttributes = $requiredAttributes;
    }

    /**
     * @return string[] of attribute codes that should always be exported
     */
    public function getRequiredAttributes(): array
    {
        return $this->requiredAttributes;
    }

    /**
     * @return string[] of attribute codes that are configured to be exported in the Connector settings
     */
    protected abstract function getConfiguredAttributes(int $storeId): array;

    /**
     * @param int $storeId
     * @return string[]
     */
    public function getAttributesToIndex(int $storeId): array
    {
        $attributeCodes = $this->getConfiguredAttributes($storeId);

        return empty($attributeCodes)
            ? []
            : array_merge($attributeCodes, $this->getRequiredAttributes());
    }
}

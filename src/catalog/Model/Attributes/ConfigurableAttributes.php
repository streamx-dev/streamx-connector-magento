<?php

namespace StreamX\ConnectorCatalog\Model\Attributes;

use StreamX\ConnectorCatalog\Model\SystemConfig\CatalogConfig;

class ConfigurableAttributes
{

    /**
     * This product attributes always be exported for configurable_children
     */
    const MINIMAL_ATTRIBUTE_SET = [
        'sku',
        'status',
        'visibility',
        'name',
        'price',
    ];

    private CatalogConfig $catalogConfig;
    private ?array $requiredAttributes = null;
    private ?bool $canIndexMediaGallery = null;

    public function __construct(CatalogConfig $catalogConfiguration)
    {
        $this->catalogConfig = $catalogConfiguration;
    }

    public function getChildrenRequiredAttributes(int $storeId): array
    {
        if (null === $this->requiredAttributes) {
            $attributes = $this->catalogConfig->getChildAttributesToIndex($storeId);

            if (empty($attributes)) {
                $this->requiredAttributes = [];
            } else {
                $this->requiredAttributes = array_merge($attributes, self::MINIMAL_ATTRIBUTE_SET);
            }
        }

        return $this->requiredAttributes;
    }

    public function canIndexMediaGallery(int $storeId): bool
    {
        if (null === $this->canIndexMediaGallery) {
            $attributes = $this->getChildrenRequiredAttributes($storeId);
            $this->canIndexMediaGallery = in_array('media_gallery', $attributes) || empty($attributes);
        }

        return $this->canIndexMediaGallery;
    }
}

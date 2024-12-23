<?php

namespace StreamX\ConnectorCatalog\Model\Attributes;

use StreamX\ConnectorCatalog\Api\CatalogConfigurationInterface;

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

    private CatalogConfigurationInterface $catalogConfig;
    private ?array $requiredAttributes = null;
    private ?bool $canIndexMediaGallery = null;

    public function __construct(CatalogConfigurationInterface $catalogConfiguration)
    {
        $this->catalogConfig = $catalogConfiguration;
    }

    public function getChildrenRequiredAttributes(int $storeId): array
    {
        if (null === $this->requiredAttributes) {
            $attributes = $this->catalogConfig->getAllowedChildAttributesToIndex($storeId);

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

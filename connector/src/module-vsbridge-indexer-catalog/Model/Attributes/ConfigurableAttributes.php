<?php

namespace Divante\VsbridgeIndexerCatalog\Model\Attributes;

use Divante\VsbridgeIndexerCatalog\Api\CatalogConfigurationInterface;

class ConfigurableAttributes
{

    /**
     * This product attributes always be exported for configurable_children
     * @var array
     */
    const MINIMAL_ATTRIBUTE_SET = [
        'sku',
        'status',
        'visibility',
        'name',
        'price',
    ];

    /**
     * @var CatalogConfigurationInterface
     */
    private $catalogConfig;

    /**
     * @var array
     */
    private $requiredAttributes;

    /**
     * @var bool
     */
    private $canIndexMediaGallery;

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

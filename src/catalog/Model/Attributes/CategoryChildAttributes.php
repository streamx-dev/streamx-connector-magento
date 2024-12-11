<?php

declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Attributes;

use StreamX\ConnectorCatalog\Model\SystemConfig\CategoryConfigInterface;

class CategoryChildAttributes
{
    /**
     * @var CategoryConfigInterface
     */
    private $config;

    public function __construct(CategoryConfigInterface $categoryConfig)
    {
        $this->config = $categoryConfig;
    }

    /**
     * Retrieve required attributes for child category
     */
    public function getRequiredAttributes(int $storeId): array
    {
        $attributes = $this->config->getAllowedChildAttributesToIndex($storeId);

        if (!empty($attributes)) {
            $attributes = array_merge($attributes, CategoryAttributes::MINIMAL_ATTRIBUTE_SET);

            return array_unique($attributes);
        }

        return $attributes;
    }
}

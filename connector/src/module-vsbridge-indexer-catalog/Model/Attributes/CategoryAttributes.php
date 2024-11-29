<?php

declare(strict_types=1);

namespace Divante\VsbridgeIndexerCatalog\Model\Attributes;

use Divante\VsbridgeIndexerCatalog\Model\SystemConfig\CategoryConfigInterface;

class CategoryAttributes
{
    /**
     * @const
     */
    const MINIMAL_ATTRIBUTE_SET = [
        'name',
        'is_active',
        'url_path',
        'url_key',
    ];

    /**
     * @var CategoryConfigInterface
     */
    private $config;

    public function __construct(CategoryConfigInterface $categoryConfig)
    {
        $this->config = $categoryConfig;
    }

    /**
     * Retrieve required attributes for category
     */
    public function getRequiredAttributes(int $storeId): array
    {
        $attributes = $this->config->getAllowedAttributesToIndex($storeId);

        if (!empty($attributes)) {
            $attributes = array_merge($attributes, self::MINIMAL_ATTRIBUTE_SET);

            return array_unique($attributes);
        }

        return $attributes;
    }

    public function canAddAvailableSortBy(int $storeId): bool
    {
        return $this->isAttributeAllowed('available_sort_by', $storeId);
    }

    public function canAddDefaultSortBy(int $storeId): bool
    {
        return $this->isAttributeAllowed('default_sort_by', $storeId);
    }

    private function isAttributeAllowed(string $attributeCode, int $storeId): bool
    {
        $allowedAttributes = $this->getRequiredAttributes($storeId);

        return empty($allowedAttributes) || in_array($attributeCode, $allowedAttributes);
    }
}

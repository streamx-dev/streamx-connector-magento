<?php

namespace StreamX\ConnectorCatalog\Model\Attribute;

use StreamX\ConnectorCatalog\Model\Attributes\AttributeDefinition;
use StreamX\ConnectorCatalog\Model\Attributes\ChildProductAttributes;
use StreamX\ConnectorCatalog\Model\Attributes\ProductAttributes;

class IndexableAttributesFilter {

    private ProductAttributes $productAttributes;
    private ChildProductAttributes $childProductAttributes;

    public function __construct(
        ProductAttributes $productAttributes,
        ChildProductAttributes $childProductAttributes
    ) {
        $this->productAttributes = $productAttributes;
        $this->childProductAttributes = $childProductAttributes;
    }

    /**
     * Filters the given attributes to return only those of the attributes that are
     * currently selected as indexable for main (non-child) products in the Connector settings.
     *
     * @param AttributeDefinition[] $attributes
     * @return int[] attribute IDs
     */
    public function filterProductAttributes(array $attributes, int $storeId): array {
        return $this->filterIndexableAttributes(
            $attributes,
            $this->productAttributes->getAttributesToIndex($storeId)
        );
    }

    /**
     * Filters the given attributes to return only those of the attributes that are
     * currently selected as indexable for child (variant) products in the Connector settings.
     *
     * @param AttributeDefinition[] $attributes
     * @return int[] attribute IDs
     */
    public function filterChildProductAttributes(array $attributes, int $storeId): array {
        return $this->filterIndexableAttributes(
            $attributes,
            $this->childProductAttributes->getAttributesToIndex($storeId)
        );
    }

    /**
     * @param AttributeDefinition[] $attributes
     * @param string[] $attributesToIndex
     * @return int[] attribute IDs
     */
    private function filterIndexableAttributes(array $attributes, array $attributesToIndex): array {
        if (empty($attributesToIndex)) {
            // empty list of attributes to index always means: index all attributes.
            return $attributes;
        }

        $result = [];
        foreach ($attributes as $attribute) {
            if (in_array($attribute->getCode(), $attributesToIndex)) {
                $result[] = $attribute->getId();
            }
        }
        return $result;
    }

    public function isIndexableProductAttribute(string $attributeCode, int $storeId): bool {
        return in_array($attributeCode, $this->productAttributes->getAttributesToIndex($storeId));
    }

    public function isIndexableChildProductAttribute(string $attributeCode, int $storeId): bool {
        return in_array($attributeCode, $this->childProductAttributes->getAttributesToIndex($storeId));
    }
}
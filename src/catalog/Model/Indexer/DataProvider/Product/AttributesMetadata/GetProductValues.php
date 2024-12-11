<?php

declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\AttributesMetadata;

class GetProductValues
{
    public function execute(array $productDTO, string $attributeCode): array
    {
        $attributeValue = isset($productDTO[$attributeCode]) ? $productDTO[$attributeCode] : '';

        if (!is_array($attributeValue)) {
            $attributeValue = [$attributeValue];
        }

        $options = $this->getOptionsForChildren($productDTO, $attributeCode);
        $options = array_merge($options, $attributeValue);

        if (!empty($options)) {
            $options = array_unique($options);
        }

        return $options;
    }

    private function getOptionsForChildren(array $productDTO, string $attributeCode): array
    {
        if (!isset($productDTO['configurable_children'])) {
            return [];
        }

        $options = [];

        foreach ($productDTO['configurable_children'] as $child) {
            if (isset($child[$attributeCode])) {
                if (is_array($child[$attributeCode])) {
                    $options = array_merge($options, $child[$attributeCode]);
                } else {
                    $options[] = $child[$attributeCode];
                }
            }
        }

        return $options;
    }
}

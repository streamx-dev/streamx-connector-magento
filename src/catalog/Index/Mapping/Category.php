<?php

declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Index\Mapping;

use Magento\Framework\DataObject;
use StreamX\ConnectorCatalog\Model\ResourceModel\Category\LoadAttributes;
use StreamX\ConnectorCore\Api\MappingInterface;
use StreamX\ConnectorCore\Api\Mapping\FieldInterface;
use StreamX\ConnectorCore\Index\Mapping\GeneralMapping;

class Category extends AbstractMapping implements MappingInterface
{
    private array $omitAttributes = [
        'children',
        'all_children',
    ];

    private GeneralMapping $generalMapping;
    private LoadAttributes $loadAttributes;
    private ?array $properties = null;

    public function __construct(
        GeneralMapping $generalMapping,
        LoadAttributes $resourceModel,
        array $staticFieldMapping
    ) {
        $this->generalMapping = $generalMapping;
        $this->loadAttributes = $resourceModel;
        parent::__construct($staticFieldMapping);
    }

    public function getMappingProperties(): array
    {
        if (null === $this->properties) {
            $attributesMapping = $this->getAllAttributesMapping();

            $properties = $this->generalMapping->getCommonProperties();
            $properties['children_count'] = ['type' => FieldInterface::TYPE_INTEGER];
            $properties['productCount'] = ['type' => FieldInterface::TYPE_INTEGER];

            $childMapping = $this->getChildrenDataMapping($attributesMapping, $properties);
            $properties['children_data'] = ['properties' => $childMapping];
            $properties = array_merge($properties, $attributesMapping);

            // grid_per_page -> not implemented yet
            $properties['grid_per_page'] = ['type' => FieldInterface::TYPE_INTEGER];
            $mapping = ['properties' => $properties];
            $mappingObject = new DataObject();
            $mappingObject->setData($mapping);

            $this->properties = $mappingObject->getData();
        }

        return $this->properties;
    }

    private function getAllAttributesMapping(): array
    {
        $attributes = $this->getAttributes();
        $allAttributesMapping = [];

        foreach ($attributes as $attribute) {
            $attributeCode = $attribute->getAttributeCode();

            if (in_array($attributeCode, $this->omitAttributes)) {
                continue;
            }

            $mapping = $this->getAttributeMapping($attribute);
            $allAttributesMapping[$attributeCode] = $mapping[$attributeCode];
        }

        $allAttributesMapping['slug'] = ['type' => FieldInterface::TYPE_KEYWORD];

        return $allAttributesMapping;
    }

    private function getChildrenDataMapping(array $allAttributesMapping, array $commonProperties): array
    {
        $childMapping = array_merge($commonProperties, $allAttributesMapping);
        unset($childMapping['created_at'], $childMapping['updated_at']);

        return $childMapping;
    }

    /**
     * Load Category attributes
     */
    public function getAttributes(): array
    {
        return $this->loadAttributes->execute();
    }
}

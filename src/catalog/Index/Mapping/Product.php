<?php

namespace StreamX\ConnectorCatalog\Index\Mapping;

use StreamX\ConnectorCore\Api\Mapping\FieldInterface;
use StreamX\ConnectorCore\Api\MappingInterface;
use StreamX\ConnectorCore\Index\Mapping\GeneralMapping;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\LoadAttributes;

class Product extends AbstractMapping implements MappingInterface // TODO AbstractMapping is used only by this class. Inline it here
{
    private GeneralMapping $generalMapping;
    private StockMapping $stockMapping;
    private LoadAttributes $resourceModel;
    private ?array $properties = null;

    /**
     * @var FieldMappingInterface[]
     */
    private array $additionalMapping;

    public function __construct(
        GeneralMapping $generalMapping,
        StockMapping $stockMapping,
        LoadAttributes $resourceModel,
        array $staticFieldMapping,
        array $additionalMapping
    ) {
        $this->stockMapping = $stockMapping;
        $this->generalMapping = $generalMapping;
        $this->resourceModel = $resourceModel;
        $this->additionalMapping = $additionalMapping;

        parent::__construct($staticFieldMapping);
    }

    public function getMappingProperties(): array
    {
        if (null === $this->properties) {
            $allAttributesMapping = $this->getAllAttributesMappingProperties();
            $commonMappingProperties = $this->getCommonMappingProperties();
            $attributesMapping = array_merge($allAttributesMapping, $commonMappingProperties);

            $properties = $this->getCustomProperties();
            $properties['configurable_children'] = ['properties' => $attributesMapping];
            $properties = array_merge($properties, $attributesMapping);
            $properties = array_merge($properties, $this->generalMapping->getCommonProperties());

            $this->properties = ['properties' => $properties];
        }

        return $this->properties;
    }

    private function getAllAttributesMappingProperties(): array
    {
        $attributes = $this->getAttributes();
        $allAttributesMapping = [];

        foreach ($attributes as $attribute) {
            $attributeCode = $attribute->getAttributeCode();
            $mapping = $this->getAttributeMapping($attribute);
            $allAttributesMapping[$attributeCode] = $mapping[$attributeCode];
        }

        $allAttributesMapping['slug'] = ['type' => FieldInterface::TYPE_KEYWORD];

        return $allAttributesMapping;
    }

    private function getCommonMappingProperties(): array
    {
        $attributesMapping = [];
        $attributesMapping['stock']['properties'] = $this->stockMapping->get();
        $attributesMapping['media_gallery'] = [
            'properties' => [
                'type' => ['type' => FieldInterface::TYPE_TEXT],
                'image' => ['type' => FieldInterface::TYPE_TEXT],
                'lab' => ['type' => FieldInterface::TYPE_TEXT],
                'pos' => ['type' => FieldInterface::TYPE_TEXT],
                'vid' => [
                    'properties' => [
                        'url' =>  ['type' => FieldInterface::TYPE_TEXT],
                        'title' =>  ['type' => FieldInterface::TYPE_TEXT],
                        'desc' =>  ['type' => FieldInterface::TYPE_TEXT],
                        'video_id' =>  ['type' => FieldInterface::TYPE_TEXT],
                        'meta' =>  ['type' => FieldInterface::TYPE_TEXT],
                        'type' =>  ['type' => FieldInterface::TYPE_TEXT],
                    ]
                ]
            ],
        ];
        $attributesMapping['final_price'] = ['type' => FieldInterface::TYPE_DOUBLE];
        $attributesMapping['regular_price'] = ['type' => FieldInterface::TYPE_DOUBLE];
        $attributesMapping['parent_sku'] = ['type' => FieldInterface::TYPE_KEYWORD];

        return $attributesMapping;
    }

    private function getCustomProperties(): array
    {
        $customProperties = ['attribute_set_id' => ['type' => FieldInterface::TYPE_LONG]];

        foreach ($this->additionalMapping as $propertyName => $properties) {
            if ($properties instanceof FieldMappingInterface) {
                $customProperties[$propertyName] = $properties->get();
            }
        }

        return $customProperties;
    }

    public function getAttributes(): array
    {
        return $this->resourceModel->execute();
    }
}

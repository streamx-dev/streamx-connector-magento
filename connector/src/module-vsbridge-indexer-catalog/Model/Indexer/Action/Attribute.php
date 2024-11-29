<?php

namespace Divante\VsbridgeIndexerCatalog\Model\Indexer\Action;

use Divante\VsbridgeIndexerCatalog\Model\ResourceModel\Attribute as ResourceModel;
use Divante\VsbridgeIndexerCatalog\Index\Mapping\Attribute as AttributeMapping;
use Divante\VsbridgeIndexerCore\Api\ConvertValueInterface;

class Attribute
{
    /**
     * @var ResourceModel
     */
    private $resourceModel;

    /**
     * @var AttributeMapping
     */
    private $attributeMapping;

    /**
     * @var ConvertValueInterface
     */
    private $convertValue;

    public function __construct(
        ConvertValueInterface $convertValue,
        AttributeMapping $attributeMapping,
        ResourceModel $resourceModel
    ) {
        $this->convertValue = $convertValue;
        $this->resourceModel = $resourceModel;
        $this->attributeMapping = $attributeMapping;
    }

    /**
     * @return \Traversable
     */
    public function rebuild(array $attributeIds = [])
    {
        $lastAttributeId = 0;

        do {
            $attributes = $this->resourceModel->getAttributes($attributeIds, $lastAttributeId);

            foreach ($attributes as $attributeData) {
                $lastAttributeId = $attributeData['attribute_id'];
                $attributeData['id'] = $attributeData['attribute_id'];
                $attributeData = $this->filterData($attributeData);

                yield $lastAttributeId => $attributeData;
            }
        } while (!empty($attributes));
    }

    private function filterData(array $attributeData): array
    {
        foreach ($attributeData as $key => $value) {
            $value = $this->convertValue->execute($this->attributeMapping, $key, $value);
            $attributeData[$key] = $value;
        }

        return $attributeData;
    }
}

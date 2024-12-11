<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\Action;

use StreamX\ConnectorCatalog\Model\ResourceModel\Attribute as ResourceModel;
use StreamX\ConnectorCatalog\Index\Mapping\Attribute as AttributeMapping;
use StreamX\ConnectorCore\Api\ConvertValueInterface;

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

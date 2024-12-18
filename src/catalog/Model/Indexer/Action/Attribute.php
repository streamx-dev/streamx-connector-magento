<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\Action;

use StreamX\ConnectorCatalog\Model\ResourceModel\Attribute as ResourceModel;
use StreamX\ConnectorCatalog\Index\Mapping\Attribute as AttributeMapping;
use StreamX\ConnectorCore\Api\ConvertValueInterface;
use Traversable;

class Attribute
{
    private ResourceModel $resourceModel;
    private AttributeMapping $attributeMapping;
    private ConvertValueInterface $convertValue;

    public function __construct(
        ConvertValueInterface $convertValue,
        AttributeMapping $attributeMapping,
        ResourceModel $resourceModel
    ) {
        $this->convertValue = $convertValue;
        $this->resourceModel = $resourceModel;
        $this->attributeMapping = $attributeMapping;
    }

    public function rebuild(array $attributeIds = []): Traversable {
        $lastAttributeId = 0;

        // 1. Publish edited and added attributes
        $publishedAttributeIds = [];
        do {
            $attributes = $this->resourceModel->getAttributes($attributeIds, $lastAttributeId);

            foreach ($attributes as $attributeData) {
                $lastAttributeId = $attributeData['attribute_id'];
                $attributeData['id'] = $attributeData['attribute_id'];
                $attributeData = $this->filterData($attributeData);

                yield $lastAttributeId => $attributeData;
                $publishedAttributeIds[] = $lastAttributeId;
            }
        } while (!empty($attributes));

        // 2. Unpublish deleted attributes
        $idsOfAttributesToUnpublish = array_diff($attributeIds, $publishedAttributeIds);
        foreach ($idsOfAttributesToUnpublish as $attributeId) {
            yield $attributeId => ['id' => $attributeId];
        }
    }

    private function filterData(array $attributeData): array {
        foreach ($attributeData as $key => $value) {
            $value = $this->convertValue->execute($this->attributeMapping, $key, $value);
            $attributeData[$key] = $value;
        }

        return $attributeData;
    }
}

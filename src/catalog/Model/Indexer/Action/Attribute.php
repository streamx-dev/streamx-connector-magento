<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\Action;

use StreamX\ConnectorCatalog\Model\ResourceModel\Attribute as ResourceModel;
use Traversable;

class Attribute implements BaseAction {

    private ResourceModel $resourceModel;

    public function __construct(ResourceModel $resourceModel) {
        $this->resourceModel = $resourceModel;
    }

    public function loadData(int $storeId = 1, array $attributeIds = []): Traversable {
        $lastAttributeId = 0;

        // 1. Publish edited and added attributes
        $publishedAttributeIds = [];
        do {
            $attributes = $this->resourceModel->getAttributes($attributeIds, $lastAttributeId, 100);
            foreach ($attributes as $attributeData) {
                $lastAttributeId = $attributeData['id'];
                yield $lastAttributeId => $attributeData;
                $publishedAttributeIds[] = $lastAttributeId;
            }
        } while (!empty($attributes));

        // 2. Unpublish deleted attributes
        $idsOfAttributesToUnpublish = array_diff($attributeIds, $publishedAttributeIds);
        foreach ($idsOfAttributesToUnpublish as $attributeId) {
            yield $attributeId => [];
        }
    }
}

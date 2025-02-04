<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\Action;

use RuntimeException;
use StreamX\ConnectorCatalog\Model\Indexer\AttributeProcessor;
use StreamX\ConnectorCatalog\Model\ResourceModel\Attribute\LoadAttributes;
use Traversable;

class Attribute implements BaseAction {

    private LoadAttributes $loadAttributes;

    public function __construct(LoadAttributes $loadAttributes) {
        $this->loadAttributes = $loadAttributes;
    }

    public function loadData(int $storeId = 1, array $attributeIds = []): Traversable {
        if (empty($attributeIds)) {
            throw new RuntimeException(
                'Indexation of all attributes is not supported.
Available solutions:
  - Use this indexer in Update By Schedule mode
  - Reindex single attributes. Example of reindexing attribute with ID 93 from store 1:
      bin/magento streamx:index streamx_attribute_indexer 1 93');
        }

        $lastAttributeId = 0;

        // 1. Publish edited and added attributes
        $publishedAttributeIds = [];
        do {
            $attributes = $this->loadAttributes->loadAttributeDefinitionsByIds($attributeIds, $storeId, $lastAttributeId, 100);
            foreach ($attributes as $attribute) {
                $lastAttributeId = $attribute->getId();
                yield $lastAttributeId => $attribute;
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

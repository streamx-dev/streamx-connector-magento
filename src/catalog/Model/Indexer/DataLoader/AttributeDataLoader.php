<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Indexer\DataLoader;

use StreamX\ConnectorCatalog\Model\ResourceModel\Product\LoadAttributeDefinitions;
use StreamX\ConnectorCore\Api\BasicDataLoader;
use Traversable;

class AttributeDataLoader implements BasicDataLoader {

    private LoadAttributeDefinitions $loadAttributeDefinitions;

    public function __construct(LoadAttributeDefinitions $loadAttributeDefinitions) {
        $this->loadAttributeDefinitions = $loadAttributeDefinitions;
    }

    /**
     * @inheritdoc
     */
    public function loadData(int $storeId, array $attributeIds): Traversable {
        $lastAttributeId = 0;

        // 1. Publish edited and added attributes
        $publishedAttributeIds = [];
        do {
            $attributes = $this->loadAttributeDefinitions->loadAttributeDefinitionsByIds($attributeIds, $storeId, $lastAttributeId, 100);
            foreach ($attributes as $attribute) {
                $lastAttributeId = $attribute->getId();
                yield $lastAttributeId => $attribute;
                $publishedAttributeIds[] = $lastAttributeId;
            }
        } while (!empty($attributes));

        // 2. Unpublish deleted attributes
        $idsOfAttributesToUnpublish = array_diff($attributeIds, $publishedAttributeIds);
        foreach ($idsOfAttributesToUnpublish as $attributeId) {
            yield $attributeId => null;
        }
    }
}

<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Attribute;

use StreamX\ConnectorCatalog\Model\Attributes\AttributeDefinition;
use StreamX\ConnectorCatalog\Model\ResourceModel\Attribute\LoadAttributes;
use StreamX\ConnectorCore\Api\DataProviderInterface;

class ProductsWithChangedAttributesProvider extends DataProviderInterface
{
    private LoadAttributes $loadAttributes;

    public function __construct(LoadAttributes $loadAttributes)
    {
        $this->loadAttributes = $loadAttributes;
    }

    /**
     * @inheritdoc
     */
    public function addData(array $indexData, int $storeId): array
    {
        // TODO:
        //  1. load definitions of all attributes in constructor (only once)
        //  2. load definitions of the indexed attrs (that have been collected as modified)
        //  3. detect changes in only the fields we care about: label (frontend_value), name (attribute_code), isFacet, options (values and labels)
        //  4. collect all products that use the attributes from point 3
        //  5. publish them all to StreamX (with batching)
        //  6. when done, update the definitions list initialized in point 1

        // FIXME return temporary response for now, for compatibility with how this class worked before
        /**
         * @var AttributeDefinition $attributeDefinition
         */
        foreach ($indexData as $attributeId => $attributeDefinition) {
            $indexData[$attributeId] = [
                'attribute_code' => $attributeDefinition->getName(),
                'frontend_label' => $attributeDefinition->getLabel(),
                'id' => $attributeDefinition->getId(),
                'is_facet' => $attributeDefinition->isFacet()
            ];
        }
        return $indexData;
    }

}

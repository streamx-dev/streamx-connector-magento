<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Attribute;

use StreamX\ConnectorCore\Api\DataProviderInterface;
use Magento\Eav\Model\ResourceModel\Entity\Attribute as EntityResource;

// TODO: currently this class provides definitions of attributes (that were added / modified / deleted)
//  The definitions are published to StreamX.
//  TODO: refactor to collect all products that use such an attribute, and publish the products instead of publishing attributes.
//  TODO: do this only when label (frontend_value) or name (attribute_code) has changed, skip changes of other fields of Attribute Definition
class Options extends DataProviderInterface
{
    private EntityResource $entityAttributeResource;

    public function __construct(EntityResource $entityResource)
    {
        $this->entityAttributeResource = $entityResource;
    }

    /**
     * @inheritdoc
     */
    public function addData(array $indexData, int $storeId): array
    {
        foreach ($indexData as $attributeId => $attributeData) {
            $storeLabels = $this->getStoreLabelsByAttributeId($attributeId);

            if (isset($storeLabels[$storeId])) {
                $attributeData['store_frontend_label'] = $storeLabels[$storeId];
            }

            $indexData[$attributeId] = $attributeData;
        }

        return $indexData;
    }

    private function getStoreLabelsByAttributeId(int $attributeId): array
    {
        return $this->entityAttributeResource->getStoreLabelsByAttributeId($attributeId);
    }
}

<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Attribute;

use StreamX\ConnectorCore\Api\DataProviderInterface;
use StreamX\ConnectorCatalog\Model\Attribute\LoadOptions;
use Magento\Eav\Model\ResourceModel\Entity\Attribute as EntityResource;

class Options implements DataProviderInterface
{

    /**
     * @var LoadOptions
     */
    private $loadOptions;

    /**
     * @var EntityResource
     */
    private $entityAttributeResource;

    public function __construct(
        LoadOptions $loadOptions,
        EntityResource $entityResource
    ) {
        $this->loadOptions = $loadOptions;
        $this->entityAttributeResource = $entityResource;
    }

    /**
     * @inheritdoc
     */
    public function addData(array $indexData, int $storeId): array
    {
        foreach ($indexData as $attributeId => $attributeData) {
            $attributeData['default_frontend_label'] = $attributeData['frontend_label'];
            $storeLabels = $this->getStoreLabelsByAttributeId($attributeId);

            if (isset($storeLabels[$storeId])) {
                $attributeData['frontend_label'] = $storeLabels[$storeId];
            }

            if ($this->useSource($attributeData)) {
                $attributeData['options'] = $this->getAttributeOptions($attributeData, $storeId);
            }

            $indexData[$attributeId] = $attributeData;
        }

        return $indexData;
    }

    public function getAttributeOptions(array $attributeData, int $storeId): array
    {
        return $this->loadOptions->execute($attributeData['attribute_code'], $storeId);
    }

    private function useSource(array $attributeData): bool
    {
        return $attributeData['frontend_input'] === 'select' || $attributeData['frontend_input'] === 'multiselect'
               || $attributeData['source_model'] != '';
    }

    private function getStoreLabelsByAttributeId(int $attributeId): array
    {
        return $this->entityAttributeResource->getStoreLabelsByAttributeId($attributeId);
    }
}

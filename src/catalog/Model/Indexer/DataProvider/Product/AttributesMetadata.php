<?php

declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use StreamX\ConnectorCatalog\Api\CatalogConfigurationInterface;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\AttributesMetadata\GetProductValues;
use StreamX\ConnectorCatalog\Model\ResourceModel\AttributeMetadata as Resource;
use StreamX\ConnectorCore\Api\DataProviderInterface;

class AttributesMetadata  implements DataProviderInterface
{
    private Resource $resourceModel;
    private GetProductValues $getProductValues;
    private CatalogConfigurationInterface $configuration;

    public function __construct(
        GetProductValues $getProductValues,
        CatalogConfigurationInterface $configuration,
        Resource $resourceModel
    ) {
        $this->resourceModel = $resourceModel;
        $this->getProductValues = $getProductValues;
        $this->configuration = $configuration;
    }

    public function addData(array $indexData, int $storeId): array
    {
        if ($this->configuration->canExportAttributesMetadata()) {
            foreach ($indexData as $productId => $productDTO) {
                $metaAttributes = $this->getAttributeMetadata($productDTO, $storeId);
                $indexData[$productId]['attributes_metadata'] = $metaAttributes;
            }
        }

        return $indexData;
    }

    private function getAttributeMetadata(array $productDTO, int $storeId): array
    {
        $attributes = $this->resourceModel->getAttributes($storeId);
        $meta = [];

        foreach ($attributes as $attribute) {
            $options = $this->getProductOptions($productDTO, $attribute, $storeId);
            $attributeData = $attribute;
            unset($attributeData['source_model']);
            $storeLabel = $this->resourceModel->getStoreLabels($attribute['id'], $storeId);

            if ($storeLabel) {
                $attributeData['default_frontend_label'] = $storeLabel;
            }

            $attributeData['options'] = $options;
            $meta[] = $attributeData;
        }

        return $meta;
    }

    private function getProductOptions(array $productDTO, array $attribute, int $storeId): array
    {
        return $this->getOptionsForOptionAttributes($productDTO, $attribute, $storeId);
    }

    private function getOptionsForOptionAttributes(array $productDTO, array $attribute, int $storeId): array
    {
        $attributeId = $attribute['attribute_id'];
        $attributeCode = $attribute['attribute_code'];
        $allOptions = $this->resourceModel->getOptions($attributeId, $storeId);

        if (empty($allOptions)) {
            return [];
        }

        $options = $this->getProductValues->execute($productDTO, $attributeCode);
        $productOptions = [];

        foreach ($options as $optionId) {
            if (isset($allOptions[$optionId])) {
                $productOptions[] = $allOptions[$optionId];
            }
        }

        return $productOptions;
    }
}

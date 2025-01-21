<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use Exception;
use StreamX\ConnectorCatalog\Model\Attributes\AttributeDefinition;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\ProductAttributesProvider;
use StreamX\ConnectorCatalog\Model\SlugGenerator;
use StreamX\ConnectorCore\Api\DataProviderInterface;
use StreamX\ConnectorCatalog\Model\SystemConfig\CatalogConfig;
use StreamX\ConnectorCatalog\Model\Attributes\ProductAttributes;
use StreamX\ConnectorCore\Indexer\ImageUrlManager;

class AttributeData implements DataProviderInterface
{
    private ProductAttributesProvider $resourceModel;
    private CatalogConfig $settings;
    private ProductAttributes $productAttributes;
    private ImageUrlManager $imageUrlManager;

    public function __construct(
        ProductAttributes $productAttributes,
        CatalogConfig $configSettings,
        ProductAttributesProvider $resourceModel,
        ImageUrlManager $imageUrlManager
    ) {
        $this->settings = $configSettings;
        $this->resourceModel = $resourceModel;
        $this->productAttributes = $productAttributes;
        $this->imageUrlManager = $imageUrlManager;
    }

    /**
     * @throws Exception
     */
    public function addData(array $indexData, int $storeId): array
    {
        $requiredAttributesMap = $this->getRequiredAttributesMap($storeId);

        $productIds = array_keys($indexData);
        $requiredAttributeCodes = array_keys($requiredAttributesMap);
        $attributesData = $this->resourceModel->loadAttributesData($storeId, $productIds, $requiredAttributeCodes);

        foreach ($indexData as &$productData) {
            $productData['attributes'] = [];
        }

        foreach ($attributesData as $entityId => $attributeCodesAndValues) {
            foreach ($attributeCodesAndValues as $attributeCode => $attributeValue) {
                $this->addAttributeToProduct($indexData[$entityId], $attributeCode, $attributeValue, $requiredAttributesMap[$attributeCode]);
            }

            $this->applySlug($indexData[$entityId]);
        }

        $attributesData = null;

        return $indexData;
    }

    private function addAttributeToProduct(array &$productData, string $attributeCode, $attributeValue, AttributeDefinition $attributeDefinition): void
    {
        if ($attributeCode == 'name' || $attributeCode == 'description') {
            $productData[$attributeCode] = $attributeValue;
        } elseif ($attributeCode == 'image') {
            $productData['primaryImage'] = [
                'url' => $this->imageUrlManager->getProductImageUrl($attributeValue)
            ];
        } elseif ($attributeCode == 'price') {
            $productData['price'] = ((float)$attributeValue);
        } else {
            $productAttribute = $this->createProductAttributeArray($attributeCode, $attributeDefinition, $attributeValue);
            $productData['attributes'][] = $productAttribute;
        }
    }

    private function createProductAttributeArray(string $attributeCode, AttributeDefinition $attributeDefinition, $attributeValue): array
    {
        $productAttribute['name'] = $attributeCode;
        $productAttribute['label'] = $attributeDefinition->getLabel();
        $productAttribute['value'] = $attributeValue;
        $productAttribute['valueLabel'] = $this->getValueLabel($attributeCode, $attributeValue, $attributeDefinition);
        $productAttribute['isFacet'] = $attributeDefinition->isFacet();

        $productAttribute['options'] = array_map(function ($option) {
            return [
                'value' => $option->getValue(),
                'label' => $option->getLabel()
            ];
        }, $attributeDefinition->getOptions());

        return $productAttribute;
    }

    private function getValueLabel(string $attributeCode, string $attributeValue, AttributeDefinition $attributeDefinition): string
    {
        if (SpecialAttributes::isSpecialAttribute($attributeCode)) {
            return SpecialAttributes::getAttributeValueLabel($attributeCode, $attributeValue);
        }
        return $attributeDefinition->getValueLabel($attributeValue);
    }

    private function applySlug(array &$productData): void
    {
        $entityId = $productData['id'];

        if ($this->settings->useMagentoUrlKeys() && isset($productData['url_key'])) {
            $productData['slug'] = $productData['url_key'];
        } else {
            $text = $productData['name'];

            if ($this->settings->useUrlKeyToGenerateSlug() && isset($productData['url_key'])) {
                $text = $productData['url_key'];
            }

            $slug = SlugGenerator::generate($text, $entityId);
            $productData['slug'] = $slug;
            $productData['url_key'] = $slug;
        }
    }

    private function getRequiredAttributesMap(int $storeId): array
    {
        $requiredAttributes = $this->productAttributes->getAttributesToIndex($storeId);

        $requiredAttributesMap = [];
        foreach ($requiredAttributes as $requiredAttribute) {
            $requiredAttributesMap[$requiredAttribute->getName()] = $requiredAttribute;
        }
        return $requiredAttributesMap;
    }
}

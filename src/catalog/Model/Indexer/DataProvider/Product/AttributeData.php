<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use Exception;
use StreamX\ConnectorCatalog\Model\Attributes\AttributeDefinition;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\AttributeDataProvider;
use StreamX\ConnectorCatalog\Model\SlugGenerator;
use StreamX\ConnectorCore\Api\DataProviderInterface;
use StreamX\ConnectorCatalog\Api\CatalogConfigurationInterface;
use StreamX\ConnectorCatalog\Model\Attributes\ProductAttributes;

class AttributeData implements DataProviderInterface
{
    private const TOP_LEVEL_ATTRIBUTES = [
        'name',
        'description',
        'price',
        'image'
    ];

    private AttributeDataProvider $resourceModel;
    private CatalogConfigurationInterface $settings;
    private ProductAttributes $productAttributes;

    public function __construct(
        ProductAttributes $productAttributes,
        CatalogConfigurationInterface $configSettings,
        AttributeDataProvider $resourceModel
    ) {
        $this->settings = $configSettings;
        $this->resourceModel = $resourceModel;
        $this->productAttributes = $productAttributes;
    }

    /**
     * @throws Exception
     */
    public function addData(array $indexData, int $storeId): array
    {
        $requiredAttributesMap = $this->getRequiredAttributesMap($storeId);

        $attributesData = $this->resourceModel->loadAttributesData($storeId, array_keys($indexData), array_keys($requiredAttributesMap));

        foreach ($indexData as &$productData) {
            $productData['attributes'] = [];
        }

        foreach ($attributesData as $entityId => $attributeCodesAndValues) {
            foreach ($attributeCodesAndValues as $attributeCode => $attributeValue) {
                if (in_array($attributeCode, self::TOP_LEVEL_ATTRIBUTES)) {
                    $indexData[$entityId][$attributeCode] = $attributeValue;
                } else {
                    $productAttribute = $this->createProductAttributeArray($attributeCode, $requiredAttributesMap[$attributeCode], $attributeValue);
                    $indexData[$entityId]['attributes'][] = $productAttribute;
                }
            }

            $this->applySlug($indexData[$entityId]);
        }

        $attributesData = null;

        return $indexData;
    }

    private function createProductAttributeArray(string $attributeCode, AttributeDefinition $attributeDefinition, $attributeValue): array
    {
        $productAttribute['name'] = $attributeCode;
        $productAttribute['label'] = $attributeDefinition->getLabel();
        $productAttribute['value'] = $attributeValue;
        $productAttribute['valueLabel'] = $this->getValueLabel($attributeCode, $attributeValue, $attributeDefinition);
        // TODO: when isFacet property is implemented - put it to $productAttribute map

        $productAttribute['options'] = [];
        foreach ($attributeDefinition->getOptions() as $option) {
            $productAttribute['options'][] = [
                'value' => $option->getValue(),
                'label' => $option->getLabel()
            ];
        }

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

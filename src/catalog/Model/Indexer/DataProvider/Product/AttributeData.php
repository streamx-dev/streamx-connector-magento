<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use Exception;
use Psr\Log\LoggerInterface;
use StreamX\ConnectorCatalog\Model\Attributes\AttributeDefinition;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\ProductAttributesProvider;
use StreamX\ConnectorCatalog\Model\SlugGenerator;
use StreamX\ConnectorCore\Api\DataProviderInterface;
use StreamX\ConnectorCatalog\Model\Attributes\ProductAttributes;
use StreamX\ConnectorCore\Indexer\ImageUrlManager;

class AttributeData implements DataProviderInterface
{
    private const IMAGE_ATTRIBUTES = [
        'image',
        'small_image',
        'thumbnail'
    ];

    private LoggerInterface $logger;
    private ProductAttributesProvider $resourceModel;
    private ProductAttributes $productAttributes;
    private ImageUrlManager $imageUrlManager;
    private SlugGenerator $slugGenerator;

    public function __construct(
        LoggerInterface $logger,
        ProductAttributes $productAttributes,
        ProductAttributesProvider $resourceModel,
        ImageUrlManager $imageUrlManager,
        SlugGenerator $slugGenerator
    ) {
        $this->logger = $logger;
        $this->resourceModel = $resourceModel;
        $this->productAttributes = $productAttributes;
        $this->imageUrlManager = $imageUrlManager;
        $this->slugGenerator = $slugGenerator;
    }

    /**
     * @throws Exception
     */
    public function addData(array $indexData, int $storeId): array
    {
        // note: the call returns empty array if the Connector is configured to export all attributes:
        $attributesToIndex = $this->productAttributes->getAttributesToIndex($storeId);

        $productIds = array_keys($indexData);

        // load attribute codes and values for each product. The call loads all available attributes if $attributesToIndex is empty
        $attributesData = $this->resourceModel->loadAttributesData($storeId, $productIds, $attributesToIndex);

        // load definitions of all attributes contained in loadAttributesData() result
        $attributeCodes = $this->collectAttributeCodes($attributesData);
        $attributeDefinitionsMap = $this->loadRequiredAttributesMap($attributeCodes, $storeId);

        foreach ($indexData as &$productData) {
            $productData['attributes'] = [];
        }

        foreach ($attributesData as $productId => $attributeCodesAndValues) {
            foreach ($attributeCodesAndValues as $attributeCode => $attributeValue) {
                $this->addAttributeToProduct($indexData[$productId], $attributeCode, $attributeValue, $attributeDefinitionsMap);
            }

            $this->applySlug($indexData[$productId]);
        }

        $attributesData = null;

        return $indexData;
    }

    private function addAttributeToProduct(array &$productData, string $attributeCode, $attributeValue, array $attributeDefinitionsMap): void
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
            $attributeDefinition = $attributeDefinitionsMap[$attributeCode];
            $productAttribute = $this->createProductAttributeArray($attributeCode, $attributeDefinition, $attributeValue);
            $productData['attributes'][] = $productAttribute;
        }
    }

    private function createProductAttributeArray(string $attributeCode, AttributeDefinition $attributeDefinition, $attributeValue): array
    {
        $productAttribute['name'] = $attributeCode;
        $productAttribute['label'] = $attributeDefinition->getLabel();

        if (is_array($attributeValue)) {
            // TODO: to be analysed. Observed for attributes such as: material, pattern, climate, style_bottom
            $this->logger->warning("Value of attribute $attributeCode is an array: " . json_encode($attributeValue));

            if (count($attributeValue) > 1) {
                $this->logger->error("Attribute $attributeCode has more than one value: " . json_encode($attributeValue) . '. Taking only the first value');
            }

            $attributeValue = $attributeValue[0];
        }

        $productAttribute['value'] = in_array($attributeCode, self::IMAGE_ATTRIBUTES)
            ? $this->imageUrlManager->getProductImageUrl($attributeValue)
            : $attributeValue;

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

    function applySlug(array &$productData): void
    {
        $slug = $this->slugGenerator->compute($productData);
        $productData['slug'] = $slug;
        $productData['url_key'] = $slug;
    }

    private function collectAttributeCodes(array $attributesData): array
    {
        $attributeCodes = [];
        foreach ($attributesData as $attributeCodesAndValues) {
            $attributeCodes = array_merge($attributeCodes, array_keys($attributeCodesAndValues));
        }
        return array_unique($attributeCodes);
    }

    private function loadRequiredAttributesMap(array $attributeCodes, int $storeId): array
    {
        $attributeDefinitions = $this->productAttributes->loadAttributeDefinitions($attributeCodes, $storeId); // TODO consider merging loadAttributesData() with loadAttributeDefinitions() to load both attribute data and definitions in a single call

        $attributeDefinitionsMap = [];
        foreach ($attributeDefinitions as $attributeDefinition) {
            $attributeDefinitionsMap[$attributeDefinition->getName()] = $attributeDefinition;
        }
        return $attributeDefinitionsMap;
    }
}

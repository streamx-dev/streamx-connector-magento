<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use Exception;
use Psr\Log\LoggerInterface;
use StreamX\ConnectorCatalog\Model\Attributes\AttributeDefinition;
use StreamX\ConnectorCatalog\Model\Attributes\BaseProductAttributes;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\LoadAttributeDefinitions;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\ProductAttributesProvider;
use StreamX\ConnectorCatalog\Model\SlugGenerator;
use StreamX\ConnectorCore\Api\DataProviderInterface;
use StreamX\ConnectorCore\Indexer\ImageUrlManager;

abstract class BaseAttributeData extends DataProviderInterface
{
    private const IMAGE_ATTRIBUTES = [
        'image',
        'small_image',
        'thumbnail'
    ];

    private LoggerInterface $logger;
    private ProductAttributesProvider $resourceModel;
    private BaseProductAttributes $productAttributes;
    private LoadAttributeDefinitions $loadAttributeDefinitions;
    private ImageUrlManager $imageUrlManager;
    private SlugGenerator $slugGenerator;
    private array $additionalAttributesToIndex = [];

    public function __construct(
        LoggerInterface $logger,
        BaseProductAttributes $productAttributes,
        LoadAttributeDefinitions $loadAttributeDefinitions,
        ProductAttributesProvider $resourceModel,
        ImageUrlManager $imageUrlManager,
        SlugGenerator $slugGenerator
    ) {
        $this->logger = $logger;
        $this->resourceModel = $resourceModel;
        $this->productAttributes = $productAttributes;
        $this->loadAttributeDefinitions = $loadAttributeDefinitions;
        $this->imageUrlManager = $imageUrlManager;
        $this->slugGenerator = $slugGenerator;
    }

    public function setAdditionalAttributesToIndex(array $additionalAttributesToIndex): void
    {
        $this->additionalAttributesToIndex = $additionalAttributesToIndex;
    }

    /**
     * @throws Exception
     */
    public function addData(array $indexData, int $storeId): array
    {
        // note: the call returns empty array if the Connector is configured to export all attributes:
        $attributesToIndex = $this->productAttributes->getAttributesToIndex($storeId);

        if (!empty($attributesToIndex)) {
            $attributesToIndex = array_unique(array_merge(
                $attributesToIndex,
                $this->additionalAttributesToIndex
            ));
        }

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
            foreach ($attributeCodesAndValues as $attributeCode => $attributeValues) {
                $this->addAttributeToProduct($indexData[$productId], $productId, $attributeCode, $attributeValues, $attributeDefinitionsMap);
            }

            $this->applySlug($indexData[$productId]);
        }

        $attributesData = null;

        return $indexData;
    }

    private function addAttributeToProduct(array &$productData, int $productId, string $attributeCode, array $attributeValues, array $attributeDefinitionsMap): void
    {
        if ($attributeCode == 'name') {
            $name = $this->getSingleAttributeValue($attributeCode, $attributeValues, $productId);
            $productData['name'] = $name;
            $productData['label'] = $name;
        } elseif($attributeCode == 'description') {
            $description = $this->getSingleAttributeValue($attributeCode, $attributeValues, $productId);
            $productData['description'] = ProductDescriptionUnwrapper::unwrapIfWrapped($description);
        } elseif ($attributeCode == 'image') {
            $productData['primaryImage'] = [
                'url' => $this->imageUrlManager->getProductImageUrl($this->getSingleAttributeValue($attributeCode, $attributeValues, $productId)),
                'alt' => null // expecting MediaGalleryData (which is executed later) to fill this field
            ];
        } elseif ($attributeCode == 'price') {
            $price = (float) $this->getSingleAttributeValue($attributeCode, $attributeValues, $productId);
            $productData['price'] = [
                'value' => $price,
                'discountedValue' => $price
            ];
        } else {
            $attributeDefinition = $attributeDefinitionsMap[$attributeCode];
            $productData['attributes'][] = $this->formatAttributeAsArray($attributeDefinition, $attributeValues);
        }
    }

    private function getSingleAttributeValue(string $attributeCode, array $attributeValues, int $productId) {
        if (empty($attributeValues)) {
            $this->logger->warning("$attributeCode has no value for $productId");
            return null;
        }
        if (count($attributeValues) > 1) {
            $this->logger->error("$attributeCode has more than one value for $productId: " . json_encode($attributeValues));
        }
        return $attributeValues[0];
    }

    private function formatAttributeAsArray(AttributeDefinition $attributeDefinition, array $attributeValues): array
    {
        return [
            'name' => $attributeDefinition->getCode(),
            'label' => $attributeDefinition->getValue(),
            'values' => array_map(function ($attributeValue) use ($attributeDefinition) {
                return $this->formatAttributeValueAsArray($attributeDefinition, $attributeValue);
            }, $attributeValues),
            'isFacet' => $attributeDefinition->isFacet()
        ];
    }

    private function formatAttributeValueAsArray(AttributeDefinition $attributeDefinition, $attributeValue): array
    {
        if (in_array($attributeDefinition->getCode(), self::IMAGE_ATTRIBUTES)) {
            $value = $this->imageUrlManager->getProductImageUrl($attributeValue);
            return [
                'value' => $value,
                'label' => $value
            ];
        }

        $potentialOptionValue = $this->getPotentialOptionValue($attributeDefinition, $attributeValue);
        if ($potentialOptionValue) {
            $value = $potentialOptionValue;
            $result = [
                'value' => $value,
                'label' => $value
            ];

            $swatch = $attributeDefinition->getOptionSwatch((int) $attributeValue);
            if ($swatch) {
                $result['swatch'] = [
                    'type' => $swatch->getType(),
                    'value' => $swatch->getValue()
                ];
            }
            return $result;
        }

        $value = (string)$attributeValue;
        return [
            'value' => $value,
            'label' => $value
        ];
    }

    /**
     * If $attributeValue is an option ID for the given $attributeDefinition - returns value of the option. Otherwise, returns null
     */
    private function getPotentialOptionValue(AttributeDefinition $attributeDefinition, $attributeValue): ?string
    {
        if (SpecialAttributes::isSpecialAttribute($attributeDefinition->getCode())) {
            $optionId = (int) $attributeValue;
            return SpecialAttributes::getAttributeValueLabel($attributeDefinition->getCode(), $optionId);
        }

        if (is_numeric($attributeValue)) {
            $potentialOptionId = (int)$attributeValue;
            foreach ($attributeDefinition->getOptions() as $option) {
                if ($option->getId() === $potentialOptionId) {
                    return $option->getValue();
                }
            }
        }
        return null;
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
        $attributeDefinitions = $this->loadAttributeDefinitions->loadAttributeDefinitionsByCodes($attributeCodes, $storeId); // TODO consider merging loadAttributesData() with loadAttributeDefinitionsByCodes() to load both attribute data and definitions in a single call

        $attributeDefinitionsMap = [];
        foreach ($attributeDefinitions as $attributeDefinition) {
            $attributeDefinitionsMap[$attributeDefinition->getCode()] = $attributeDefinition;
        }
        return $attributeDefinitionsMap;
    }
}

<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use Exception;
use StreamX\ConnectorCatalog\Model\Attributes\AttributeDefinitionDto;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\AttributeDataProvider;
use StreamX\ConnectorCatalog\Model\SlugGenerator;
use StreamX\ConnectorCore\Api\DataProviderInterface;
use StreamX\ConnectorCatalog\Api\CatalogConfigurationInterface;
use StreamX\ConnectorCatalog\Model\ProductUrlPathGenerator;
use StreamX\ConnectorCatalog\Model\Attributes\ProductAttributes;

class AttributeData implements DataProviderInterface
{
    private AttributeDataProvider $resourceModel;
    private CatalogConfigurationInterface $settings;
    private ProductAttributes $productAttributes;
    private ProductUrlPathGenerator $productUrlPathGenerator;

    public function __construct(
        ProductAttributes $productAttributes,
        CatalogConfigurationInterface $configSettings,
        ProductUrlPathGenerator $productUrlPathGenerator,
        AttributeDataProvider $resourceModel
    ) {
        $this->settings = $configSettings;
        $this->resourceModel = $resourceModel;
        $this->productAttributes = $productAttributes;
        $this->productUrlPathGenerator = $productUrlPathGenerator;
    }

    /**
     * @throws Exception
     */
    public function addData(array $indexData, int $storeId): array
    {
        $requiredAttributes = $this->productAttributes->getAttributesToIndex($storeId);

        $requiredAttributesMap = [];
        foreach ($requiredAttributes as $requiredAttribute) {
            $requiredAttributesMap[$requiredAttribute->getName()] = $requiredAttribute;
        }

        $attributes = $this->resourceModel->loadAttributesData($storeId, array_keys($indexData), array_keys($requiredAttributesMap));

        foreach ($indexData as $entityId => $productData) {
            $indexData[$entityId]['attributes'] = [];
        }

        foreach ($attributes as $entityId => $attributesNamesAndValues) {
            foreach ($attributesNamesAndValues as $attributeName => $attributeValue) {
                if (in_array($attributeName, [ 'name', 'description', 'price', 'image'])) {
                    $indexData[$entityId][$attributeName] = $attributeValue;
                } else {
                    $productAttribute['name'] = $attributeName;
                    $productAttribute['label'] = $requiredAttributesMap[$attributeName]->getLabel();
                    // TODO: when attribute options are implemented - put them to $productAttribute
                    // TODO: when attribute isFacet is implemented - put it to $productAttribute
                    $productAttribute['value'] = $attributeValue;
                    $productAttribute['valueLabel'] = $attributeValue;
                    $indexData[$entityId]['attributes'][] = $productAttribute;
                }
            }

            $this->applySlug($indexData[$entityId]);
        }

        $attributes = null;
        return $this->productUrlPathGenerator->addUrlPath($indexData, $storeId);
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
}

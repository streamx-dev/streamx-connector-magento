<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use Exception;
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
        $requiredAttributes = $this->productAttributes->getAttributes($storeId);
        $attributes = $this->resourceModel->loadAttributesData($storeId, array_keys($indexData), $requiredAttributes);

        foreach ($indexData as $entityId => $productData) {
            $indexData[$entityId]['attributes'] = [];
        }

        foreach ($attributes as $entityId => $attributesData) {
            $productData = $indexData[$entityId];
            $productData['attributes'] = array_merge($productData['attributes'], $attributesData);

            // TODO: those attributes should not be outputted as name + value pairs, but should be objects matching Unified Data Model structure
            $this->moveFieldsFromAttributesArrayToProductRoot($productData, $attributesData,
                'name',
                'description',
                'price',
                'image'
            );

            $this->applySlug($productData);
            $indexData[$entityId] = $productData;
        }

        $attributes = null;
        return $this->productUrlPathGenerator->addUrlPath($indexData, $storeId);
    }

    private function moveFieldsFromAttributesArrayToProductRoot(array &$productData, array $attributesData, string... $attributeCodes): void
    {
        foreach ($attributeCodes as $attributeCode) {
            if (isset($attributesData[$attributeCode])) {
                $productData[$attributeCode] = $attributesData[$attributeCode];
                unset($productData['attributes'][$attributeCode]);
            } else {
                $productData[$attributeCode] = null;
            }
        }
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

<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use StreamX\ConnectorCatalog\Model\ResourceModel\Product\AttributeDataProvider;
use StreamX\ConnectorCore\Api\DataProviderInterface;
use StreamX\ConnectorCore\Indexer\DataFilter;
use StreamX\ConnectorCatalog\Api\CatalogConfigurationInterface;
use StreamX\ConnectorCatalog\Api\SlugGeneratorInterface;
use StreamX\ConnectorCatalog\Model\ProductUrlPathGenerator;

class AttributeData implements DataProviderInterface
{
    private AttributeDataProvider $resourceModel;
    private CatalogConfigurationInterface $settings;
    private SlugGeneratorInterface $slugGenerator;
    private ProductUrlPathGenerator $productUrlPathGenerator;

    public function __construct(
        CatalogConfigurationInterface $configSettings,
        SlugGeneratorInterface $slugGenerator,
        ProductUrlPathGenerator $productUrlPathGenerator,
        AttributeDataProvider $resourceModel
    ) {
        $this->slugGenerator = $slugGenerator;
        $this->settings = $configSettings;
        $this->resourceModel = $resourceModel;
        $this->productUrlPathGenerator = $productUrlPathGenerator;
    }

    /**
     * @throws \Exception
     */
    public function addData(array $indexData, int $storeId): array
    {
        $attributes = $this->resourceModel->loadAttributesData($storeId, array_keys($indexData));

        foreach ($attributes as $entityId => $attributesData) {
            $productData = array_merge($indexData[$entityId], $attributesData);
            $productData = $this->applySlug($productData);
            $indexData[$entityId] = $productData;
        }

        $attributes = null;
        $indexData = $this->productUrlPathGenerator->addUrlPath($indexData, $storeId);

        return $indexData;
    }

    private function applySlug(array $productData): array
    {
        $entityId = $productData['id'];

        if ($this->settings->useMagentoUrlKeys() && isset($productData['url_key'])) {
            $productData['slug'] = $productData['url_key'];
        } else {
            $text = $productData['name'];

            if ($this->settings->useUrlKeyToGenerateSlug() && isset($productData['url_key'])) {
                $text = $productData['url_key'];
            }

            $slug = $this->slugGenerator->generate($text, $entityId);
            $productData['slug'] = $slug;
            $productData['url_key'] = $slug;
        }

        return $productData;
    }
}

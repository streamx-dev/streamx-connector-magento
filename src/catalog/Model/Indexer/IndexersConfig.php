<?php

namespace StreamX\ConnectorCatalog\Model\Indexer;

use Exception;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Attribute\ProductsWithChangedAttributesProvider;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Category\CategoryDataFormatter;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\BundleOptionsData;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\CategoryData;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\ConfigurableData;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\CustomOptions;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\DataCleaner;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\LangData;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\PriceData;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\ProductAttributeData;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\ProductMediaGalleryData;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\QuantityData;
use StreamX\ConnectorCore\Api\Index\TypeInterface;
use StreamX\ConnectorCore\Api\IndexersConfigInterface;
use StreamX\ConnectorCore\Index\Type;
use StreamX\ConnectorCore\Indexer\DataProviderProcessorFactory;

class IndexersConfig implements IndexersConfigInterface
{
    /** @var TypeInterface[] */
    private array $types;

    public function __construct(DataProviderProcessorFactory $dataProviderFactory)
    {
        $this->types = [
            new Type('product', [
                $dataProviderFactory->get(LangData::class),
                $dataProviderFactory->get(ProductAttributeData::class),
                // TODO review the provider; trim data produced by it only what we need
                $dataProviderFactory->get(BundleOptionsData::class),
                $dataProviderFactory->get(CategoryData::class),
                // TODO decide which of the prices added by this provider do we need
                $dataProviderFactory->get(PriceData::class),
                $dataProviderFactory->get(ProductMediaGalleryData::class),
                $dataProviderFactory->get(QuantityData::class),
                $dataProviderFactory->get(ConfigurableData::class),
                // TODO review the provider; trim data produced by it only what we need
                $dataProviderFactory->get(CustomOptions::class),
                $dataProviderFactory->get(DataCleaner::class),
            ]),
            new Type('category', [
                $dataProviderFactory->get(CategoryDataFormatter::class),
            ]),
            new Type('attribute', [
                // TODO: consider making the streamx_attribute_indexer not visible in Magento UI nor in command line indexer tools
                //  - because in full reindex mode it would publish a lot of products for every attribute present in DB
                $dataProviderFactory->get(ProductsWithChangedAttributesProvider::class),
            ])
        ];
    }

    public function getByName(string $typeName): TypeInterface
    {
        foreach ($this->types as $type) {
            if ($type->getName() === $typeName) {
                return $type;
            }
        }
        throw new Exception("Indexer configuration for $typeName not found");
    }
}

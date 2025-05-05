<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Indexer;

use Exception;
use StreamX\ConnectorCatalog\Indexer\AttributeIndexer;
use StreamX\ConnectorCatalog\Indexer\CategoryIndexer;
use StreamX\ConnectorCatalog\Indexer\ProductIndexer;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Category\CategoryDataFormatter;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\CategoryData;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\ConfigurableData;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\DataCleaner;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\IndexedPricesProvider;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\LangData;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\MediaGalleryData;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\ProductAttributeData;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\QuantityData;
use StreamX\ConnectorCore\Api\IndexersConfigInterface;
use StreamX\ConnectorCore\Index\IndexerDefinition;

class IndexersConfig implements IndexersConfigInterface {

    private IndexerDefinition $productIndexerDefinition;
    private IndexerDefinition $categoryIndexerDefinition;
    private IndexerDefinition $attributeIndexerDefinition;

    public function __construct(
        LangData              $langData,
        ProductAttributeData  $productAttributeData,
        CategoryData          $categoryData,
        IndexedPricesProvider $indexedPricesProvider,
        MediaGalleryData      $mediaGalleryData,
        QuantityData          $quantityData,
        ConfigurableData      $configurableData,
        DataCleaner           $dataCleaner,
        CategoryDataFormatter $categoryDataFormatter
    ) {
        $this->productIndexerDefinition = new IndexerDefinition(
            ProductIndexer::INDEXER_ID,
            $langData,
            $productAttributeData,
            $categoryData,
            $indexedPricesProvider,
            $mediaGalleryData,
            $quantityData,
            $configurableData,
            $dataCleaner
        );
        $this->categoryIndexerDefinition = new IndexerDefinition(
            CategoryIndexer::INDEXER_ID,
            $categoryDataFormatter
        );
        $this->attributeIndexerDefinition = new IndexerDefinition(
            AttributeIndexer::INDEXER_ID
        );
    }

    public function getById(string $indexerId): IndexerDefinition {
        switch ($indexerId) {
            case ProductIndexer::INDEXER_ID:
                return $this->productIndexerDefinition;
            case CategoryIndexer::INDEXER_ID:
                return $this->categoryIndexerDefinition;
            case AttributeIndexer::INDEXER_ID:
                return $this->attributeIndexerDefinition;
        }
        throw new Exception("Indexer $indexerId not found");
    }
}

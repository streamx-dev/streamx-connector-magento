<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Indexer;

use Exception;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Category\CategoryDataFormatter;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\BundleOptionsData;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\CategoryData;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\ConfigurableData;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\DataCleaner;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\LangData;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\MediaGalleryData;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\IndexedPricesProvider;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\ProductAttributeData;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\QuantityData;
use StreamX\ConnectorCore\Api\IndexersConfigInterface;
use StreamX\ConnectorCore\Index\IndexerDefinition;
use StreamX\ConnectorCore\Indexer\DataProviderProcessorFactory;

class IndexersConfig implements IndexersConfigInterface
{
    /** @var IndexerDefinition[] */
    private array $indexerDefinitions;

    public function __construct(DataProviderProcessorFactory $dataProviderFactory)
    {
        $this->indexerDefinitions = [
            new IndexerDefinition(ProductProcessor::INDEXER_ID, [
                $dataProviderFactory->get(LangData::class),
                $dataProviderFactory->get(ProductAttributeData::class),
                // TODO review the provider; trim data produced by it only what we need
                $dataProviderFactory->get(BundleOptionsData::class),
                $dataProviderFactory->get(CategoryData::class),
                $dataProviderFactory->get(IndexedPricesProvider::class),
                $dataProviderFactory->get(MediaGalleryData::class),
                $dataProviderFactory->get(QuantityData::class),
                $dataProviderFactory->get(ConfigurableData::class),
                $dataProviderFactory->get(DataCleaner::class),
            ]),
            new IndexerDefinition(CategoryProcessor::INDEXER_ID, [
                $dataProviderFactory->get(CategoryDataFormatter::class),
            ]),
            new IndexerDefinition(AttributeProcessor::INDEXER_ID, [])
        ];
    }

    public function getByName(string $indexerName): IndexerDefinition
    {
        foreach ($this->indexerDefinitions as $index) {
            if ($index->getName() === $indexerName) {
                return $index;
            }
        }
        throw new Exception("Indexer $indexerName not found");
    }
}

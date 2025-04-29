<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Indexer;

use Magento\Framework\Indexer\IndexerRegistry;
use Psr\Log\LoggerInterface;
use StreamX\ConnectorCatalog\Model\Attributes\AttributeDefinition;
use StreamX\ConnectorCatalog\Model\Indexer\DataLoader\AttributeDataLoader;
use StreamX\ConnectorCatalog\Model\Indexer\DataLoader\ProductDataLoader;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product;
use StreamX\ConnectorCore\Api\IndexersConfigInterface;
use StreamX\ConnectorCore\Client\StreamxAvailabilityCheckerFactory;
use StreamX\ConnectorCore\Client\StreamxClientFactory;
use StreamX\ConnectorCore\Config\OptimizationSettings;
use StreamX\ConnectorCore\Indexer\BaseStreamxIndexer;
use StreamX\ConnectorCore\Indexer\IndexedStoresProvider;
use StreamX\ConnectorCore\Client\StreamxClient;
use StreamX\ConnectorCore\System\GeneralConfig;
use Traversable;

// TODO implement checking if only relevant attribute properties have changed to trigger publishing products
class AttributeIndexer extends BaseStreamxIndexer {

    public const INDEXER_ID = 'streamx_attribute_indexer';

    private Product $productModel;
    private ProductIndexer $productsIndexer;
    private ProductDataLoader $productDataLoader;

    public function __construct(
        GeneralConfig $connectorConfig,
        IndexedStoresProvider $indexedStoresProvider,
        AttributeDataLoader $dataLoader,
        LoggerInterface $logger,
        OptimizationSettings $optimizationSettings,
        StreamxClientFactory $streamxClientFactory,
        StreamxAvailabilityCheckerFactory $streamxAvailabilityCheckerFactory,
        IndexerRegistry $indexerRegistry,
        IndexersConfigInterface $indexersConfig,
        Product $productModel,
        ProductIndexer $productsIndexer,
        ProductDataLoader $productDataLoader
    ) {
        parent::__construct(
            $connectorConfig,
            $indexedStoresProvider,
            $dataLoader,
            $logger,
            $optimizationSettings,
            $streamxClientFactory,
            $streamxAvailabilityCheckerFactory,
            $indexerRegistry,
            $indexersConfig
        );
        $this->productModel = $productModel;
        $this->productsIndexer = $productsIndexer;
        $this->productDataLoader = $productDataLoader;
    }

    /**
     * Override to instead of publishing attributes -> publish products that use those attributes
     * @param Traversable<AttributeDefinition> $attributeDefinitions
     */
    protected function ingestEntities(Traversable $attributeDefinitions, int $storeId, StreamxClient $client): void {
        $changedAttributeIds = [];

        /** @var $attributeDefinition AttributeDefinition */
        foreach ($attributeDefinitions as $attributeDefinition) {
            if ($attributeDefinition === null) {
                // a deleted attribute. Reindexing products that used it is handled in UpdateAttributeDataPlugin
                continue;
            }
            $this->logger->info("Definition of attribute '{$attributeDefinition->getCode()}' has changed");
            $changedAttributeIds[] = $attributeDefinition->getId();
        }

        $productIds = $this->productModel->loadIdsOfProductsThatUseAttributes($changedAttributeIds, $storeId);
        if (empty($productIds)) {
            // no changes in the attributes that should cause republishing products
            return;
        }

        $this->logger->info("Detected the following products to re-publish due to attribute definition change: " . json_encode($productIds));

        $products = $this->productDataLoader->loadData($storeId, $productIds);
        $products = $this->filterProductsToPublish($products);
        $this->productsIndexer->ingestEntities($products, $storeId, $client);
    }

    private function filterProductsToPublish(Traversable $products): Traversable {
        foreach ($products as $id => $product) {
            if ($product) {
                yield $id => $product;
            }
        }
    }
}
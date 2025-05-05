<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Indexer;

use StreamX\ConnectorCatalog\Model\Attribute\IndexableAttributesFilter;
use StreamX\ConnectorCatalog\Model\Attributes\AttributeDefinition;
use StreamX\ConnectorCatalog\Model\Indexer\DataLoader\AttributeDataLoader;
use StreamX\ConnectorCatalog\Model\Indexer\DataLoader\ProductDataLoader;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product;
use StreamX\ConnectorCore\Indexer\BaseStreamxIndexer;
use StreamX\ConnectorCore\Client\StreamxClient;
use StreamX\ConnectorCore\Indexer\StreamxIndexerServices;
use Traversable;

class AttributeIndexer extends BaseStreamxIndexer {

    public const INDEXER_ID = 'streamx_attribute_indexer';

    private IndexableAttributesFilter $indexableAttributesFilter;
    private Product $productModel;
    private ProductIndexer $productsIndexer;
    private ProductDataLoader $productDataLoader;

    public function __construct(
        StreamxIndexerServices $indexerServices,
        AttributeDataLoader $dataLoader,
        IndexableAttributesFilter $indexableAttributesFilter,
        Product $productModel,
        ProductIndexer $productsIndexer,
        ProductDataLoader $productDataLoader
    ) {
        parent::__construct($indexerServices, $dataLoader);
        $this->indexableAttributesFilter = $indexableAttributesFilter;
        $this->productModel = $productModel;
        $this->productsIndexer = $productsIndexer;
        $this->productDataLoader = $productDataLoader;
    }

    /**
     * Override to instead of publishing attributes -> publish products that use those attributes
     * @param Traversable<AttributeDefinition> $attributeDefinitions
     */
    protected function ingestEntities(Traversable $attributeDefinitions, int $storeId, StreamxClient $client): void {
        $addedOrEditedAttributes = [];

        /** @var $attributeDefinition AttributeDefinition */
        foreach ($attributeDefinitions as $attributeDefinition) {
            if ($attributeDefinition !== null) {
                $addedOrEditedAttributes[] = $attributeDefinition;
            } // else: a deleted attribute. Reindexing products that used it is handled in UpdateAttributeDataPlugin
        }

        $indexableProductAttributes = $this->indexableAttributesFilter->filterProductAttributes($addedOrEditedAttributes, $storeId);
        $indexableChildProductAttributes = $this->indexableAttributesFilter->filterChildProductAttributes($addedOrEditedAttributes, $storeId);

        $productIds = [];
        array_push($productIds, ...$this->productModel->loadIdsOfProductsThatUseAttributes($indexableProductAttributes, $storeId));
        array_push($productIds, ...$this->productModel->loadIdsOfChildProductsThatUseAttributes($indexableChildProductAttributes, $storeId));

        if (!empty($productIds)) {
            // TODO to be considered: check if only relevant attribute properties have changed to trigger publishing products (only changes in code, label, isFacet and options should matter)
            $this->logger->info("Detected the following products to re-publish due to attribute definition change: " . json_encode($productIds));

            $products = $this->productDataLoader->loadData($storeId, $productIds);
            $products = $this->removeProductsThatWouldBeUnpublished($products);
            $this->productsIndexer->ingestEntities($products, $storeId, $client);
        }
    }

    private function removeProductsThatWouldBeUnpublished(Traversable $products): Traversable {
        foreach ($products as $id => $product) {
            if (!empty($product)) {
                yield $id => $product;
            }
        }
    }
}
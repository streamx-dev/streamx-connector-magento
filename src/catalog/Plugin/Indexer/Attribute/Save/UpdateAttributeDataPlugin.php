<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Plugin\Indexer\Attribute\Save;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Store\Model\Store;
use StreamX\ConnectorCatalog\Indexer\AttributeIndexer;
use StreamX\ConnectorCatalog\Indexer\ProductIndexer;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product as ProductModel;
use StreamX\ConnectorCore\Indexer\IndexedStoresProvider;

class UpdateAttributeDataPlugin {

    private AttributeIndexer $attributeIndexer;
    private ProductIndexer $productIndexer;
    private ProductModel $productModel;
    private IndexedStoresProvider $indexedStoresProvider;
    private array $productIdsToReindexByAttributeId = [];

    public function __construct(
        AttributeIndexer $attributeIndexer,
        ProductIndexer $productIndexer,
        ProductModel $productModel,
        IndexedStoresProvider $indexedStoresProvider
    ) {
        $this->attributeIndexer = $attributeIndexer;
        $this->productIndexer = $productIndexer;
        $this->productModel = $productModel;
        $this->indexedStoresProvider = $indexedStoresProvider;
    }

    /**
     * Called after attribute was added or deleted: reindex the attribute
     */
    public function afterAfterSave(Attribute $attribute): Attribute {
        $this->attributeIndexer->reindexRow($attribute->getId());
        return $attribute;
    }

    /**
     * Called just before attribute is deleted, but it still exists: collect IDs of products that still use it
     */
    public function beforeDelete(Attribute $attribute): Attribute {
        $attributeId = $attribute->getId();

        $productIdsToReindex = [];
        foreach ($this->indexedStoresProvider->getStores() as $store) {
            $storeId = (int)$store->getId();
            array_push($productIdsToReindex, ...$this->productModel->loadIdsOfProductsThatUseAttributes([$attributeId], $storeId));
        }
        if (!empty($productIdsToReindex)) {
            $this->productIdsToReindexByAttributeId[$attributeId] = array_unique($productIdsToReindex);
        }
        return $attribute;
    }

    /**
     * Called after attribute was deleted: reindex the attribute, and reindex all products that were using it
     */
    public function afterAfterDeleteCommit(Attribute $attribute): Attribute {
        $attributeId = $attribute->getId();
        $this->attributeIndexer->reindexRow($attributeId);

        $productIdsToReindex = $this->productIdsToReindexByAttributeId[$attributeId] ?? null;
        if ($productIdsToReindex) {
            $this->productIndexer->reindexList($productIdsToReindex);
            unset($this->productIdsToReindexByAttributeId[$attributeId]);
        }

        return $attribute;
    }
}

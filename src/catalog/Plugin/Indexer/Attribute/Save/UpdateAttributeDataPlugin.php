<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Plugin\Indexer\Attribute\Save;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use StreamX\ConnectorCatalog\Indexer\AttributeIndexer;
use StreamX\ConnectorCatalog\Indexer\ProductIndexer;
use StreamX\ConnectorCatalog\Model\Attribute\IndexableAttributesFilter;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product as ProductModel;
use StreamX\ConnectorCore\Indexer\IndexedStoresProvider;

class UpdateAttributeDataPlugin {

    private AttributeIndexer $attributeIndexer;
    private ProductIndexer $productIndexer;
    private ProductModel $productModel;
    private IndexableAttributesFilter $indexableAttributesFilter;
    private IndexedStoresProvider $indexedStoresProvider;
    private array $productIdsToReindexByAttributeId = [];

    public function __construct(
        AttributeIndexer $attributeIndexer,
        ProductIndexer $productIndexer,
        ProductModel $productModel,
        IndexableAttributesFilter $indexableAttributesFilter,
        IndexedStoresProvider $indexedStoresProvider
    ) {
        $this->attributeIndexer = $attributeIndexer;
        $this->productIndexer = $productIndexer;
        $this->productModel = $productModel;
        $this->indexableAttributesFilter = $indexableAttributesFilter;
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
     * Called just before attribute is deleted, but it still exists: collect IDs of products that still use it.
     * When the delete operation is committed - reindex products that used this attribute (see afterAfterDeleteCommit)
     */
    public function beforeDelete(Attribute $attribute): Attribute {
        if ($this->productIndexer->isIndexerScheduled()) {
            // let the MView feature detect which products to reindex due to one of their attributes being deleted
            return $attribute;
        }

        $attributeId = $attribute->getId();

        $productIdsToReindex = [];
        $childProductIdsToReindex = [];
        foreach ($this->indexedStoresProvider->getStores() as $store) {
            $storeId = (int)$store->getId();
            $this->addProductsThatUseAttribute($attribute, $storeId, $productIdsToReindex);
            $this->addChildProductsThatUseAttribute($attribute, $storeId, $childProductIdsToReindex);
        }
        $this->productIdsToReindexByAttributeId[$attributeId] = array_unique(array_merge($productIdsToReindex, $childProductIdsToReindex));

        return $attribute;
    }

    private function addProductsThatUseAttribute(Attribute $attribute, int $storeId, array &$productIdsToReindex): void {
        if ($this->indexableAttributesFilter->isIndexableProductAttribute($attribute->getAttributeCode(), $storeId)) {
            $productsThatUseAttribute = $this->productModel->loadIdsOfProductsThatUseAttributes([$attribute->getId()], $storeId);
            array_push($productIdsToReindex, ...$productsThatUseAttribute);
        }
    }

    private function addChildProductsThatUseAttribute(Attribute $attribute, int $storeId, array &$productIdsToReindex): void {
        if ($this->indexableAttributesFilter->isIndexableChildProductAttribute($attribute->getAttributeCode(), $storeId)) {
            $productsThatUseAttribute = $this->productModel->loadIdsOfChildProductsThatUseAttributes([$attribute->getId()], $storeId);
            array_push($productIdsToReindex, ...$productsThatUseAttribute);
        }
    }

    /**
     * Called after attribute was deleted: reindex all products that were using it
     */
    public function afterAfterDeleteCommit(Attribute $attribute): Attribute {
        if ($this->productIndexer->isIndexerScheduled()) {
            // in such case, product-attribute relation rows will be deleted from database, and IDs of the affected products will be collected by the MView feature. Products indexer will be executed according to schedule
            return $attribute;
        }

        $attributeId = $attribute->getId();

        $productIdsToReindex = $this->productIdsToReindexByAttributeId[$attributeId] ?? [];
        if (!empty($productIdsToReindex)) {
            $this->productIndexer->reindexList($productIdsToReindex, false, false);
            unset($this->productIdsToReindexByAttributeId[$attributeId]);
        }

        return $attribute;
    }
}

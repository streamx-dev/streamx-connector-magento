<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Plugin\Indexer\Attribute\Save;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Store\Model\Store;
use StreamX\ConnectorCatalog\Model\Indexer\AttributeProcessor;
use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product as ProductModel;
use StreamX\ConnectorCore\Indexer\IndexableStoresProvider;

class UpdateAttributeDataPlugin {

    private AttributeProcessor $attributeProcessor;
    private ProductProcessor $productProcessor;
    private ProductModel $productModel;
    private IndexableStoresProvider $indexableStoresProvider;
    private array $productIdsToReindexByAttributeId = [];

    public function __construct(
        AttributeProcessor $attributeProcessor,
        ProductProcessor $productProcessor,
        ProductModel $productModel,
        IndexableStoresProvider $indexableStoresProvider
    ) {
        $this->attributeProcessor = $attributeProcessor;
        $this->productProcessor = $productProcessor;
        $this->productModel = $productModel;
        $this->indexableStoresProvider = $indexableStoresProvider;
    }

    /**
     * Called after attribute was added or deleted: reindex the attribute
     */
    public function afterAfterSave(Attribute $attribute): Attribute {
        $this->attributeProcessor->reindexRow($attribute->getId());
        return $attribute;
    }

    /**
     * Called just before attribute is deleted, but it still exists: collect IDs of products that still use it
     */
    public function beforeDelete(Attribute $attribute): Attribute {
        $attributeId = $attribute->getId();

        $productIdsToReindex = [];
        foreach ($this->indexableStoresProvider->getStores() as $store) {
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
        $this->attributeProcessor->reindexRow($attributeId);

        $productIdsToReindex = $this->productIdsToReindexByAttributeId[$attributeId] ?? null;
        if ($productIdsToReindex) {
            $this->productProcessor->reindexList($productIdsToReindex);
            unset($this->productIdsToReindexByAttributeId[$attributeId]);
        }

        return $attribute;
    }
}

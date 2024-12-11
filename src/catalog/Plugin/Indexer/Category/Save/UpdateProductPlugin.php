<?php

namespace StreamX\ConnectorCatalog\Plugin\Indexer\Category\Save;

use Magento\Catalog\Model\Category;
use StreamX\ConnectorCatalog\Model\Indexer\ProductCategoryProcessor;
use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;

class UpdateProductPlugin
{
    private ProductCategoryProcessor $productCategoryProcessor;
    private ProductProcessor $productProcessor;

    public function __construct(
        ProductProcessor $productProcessor,
        ProductCategoryProcessor $processor
    ) {
        $this->productProcessor = $productProcessor;
        $this->productCategoryProcessor = $processor;
    }

    /**
     * Update product category data in ES after changing category products
     */
    public function afterSave(Category $category): Category
    {
        $isChangedProductList = $category->getData('is_changed_product_list');

        if (!$isChangedProductList) {
            return $category;
        }

        if (!$this->productProcessor->isIndexerScheduled() && !$this->productCategoryProcessor->isIndexerScheduled()) {
            $this->productCategoryProcessor->reindexList($category->getAffectedProductIds());
        }

        return $category;
    }
}

<?php

namespace StreamX\ConnectorCatalog\Plugin\Indexer\Category\Save;

use StreamX\ConnectorCatalog\Model\Indexer\ProductCategoryProcessor;
use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;

class UpdateProductPlugin
{
    /**
     * @var ProductCategoryProcessor
     */
    private $productCategoryProcessor;

    /**
     * @var ProductProcessor
     */
    private $productProcessor;

    public function __construct(
        ProductProcessor $productProcessor,
        ProductCategoryProcessor $processor
    ) {
        $this->productProcessor = $productProcessor;
        $this->productCategoryProcessor = $processor;
    }

    /**
     * Update product category data in ES after changing category products
     *
     * @return \Magento\Catalog\Model\Category
     */
    public function afterSave(\Magento\Catalog\Model\Category $category)
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

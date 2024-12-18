<?php

namespace StreamX\ConnectorCatalog\Plugin\Indexer\Category\Save;

use StreamX\ConnectorCatalog\Model\Indexer\CategoryProcessor;
use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use StreamX\ConnectorCatalog\Model\ResourceModel\Category as CategoryResourceModel;

use Magento\Catalog\Model\Category;

class UpdateCategoryDataPlugin
{
    private CategoryResourceModel $resourceModel;
    private CategoryProcessor $categoryProcessor;
    private ProductProcessor $productProcessor;

    public function __construct(
        CategoryResourceModel $resourceModel,
        CategoryProcessor $categoryProcessor,
        ProductProcessor $productProcessor
    ) {
        $this->resourceModel = $resourceModel;
        $this->categoryProcessor = $categoryProcessor;
        $this->productProcessor = $productProcessor;
    }

    /**
     * Reindex data after product save/delete resource commit
     */
    public function afterReindex(Category $category): void
    {
        $categoryIds = [];
        $originalUrlKey = $category->getOrigData('url_key');
        $urlKey = $category->getData('url_key');
        $categoryId = (int) $category->getId();

        if (!$category->isObjectNew() && $originalUrlKey !== $urlKey) {
            $categoryIds = $this->resourceModel->getAllSubCategories($categoryId);
        }

        $categoryIds[] = $categoryId;

        $this->categoryProcessor->reindexList($categoryIds);
        $this->reindexAffectedProducts($category);
    }

    private function reindexAffectedProducts(Category $category): void{
        if (!$this->productProcessor->isIndexerScheduled()) {
            $isChangedProductList = $category->getData('is_changed_product_list');

            if ($isChangedProductList) {
                $this->productProcessor->reindexList($category->getAffectedProductIds());
            }
        }
    }
}

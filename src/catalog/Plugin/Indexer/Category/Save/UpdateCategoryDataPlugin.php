<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Plugin\Indexer\Category\Save;

use StreamX\ConnectorCatalog\Indexer\CategoryIndexer;
use StreamX\ConnectorCatalog\Indexer\ProductIndexer;
use StreamX\ConnectorCatalog\Model\ResourceModel\Category as CategoryResourceModel;

use Magento\Catalog\Model\Category;

class UpdateCategoryDataPlugin
{
    private CategoryResourceModel $resourceModel;
    private CategoryIndexer $categoryIndexer;
    private ProductIndexer $productIndexer;

    public function __construct(
        CategoryResourceModel $resourceModel,
        CategoryIndexer $categoryIndexer,
        ProductIndexer $productIndexer
    ) {
        $this->resourceModel = $resourceModel;
        $this->categoryIndexer = $categoryIndexer;
        $this->productIndexer = $productIndexer;
    }

    /**
     * Reindex data after category save/delete resource commit
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

        $this->categoryIndexer->reindexList($categoryIds);
        $this->reindexAffectedProducts($category);
    }

    private function reindexAffectedProducts(Category $category): void{
        if (!$this->productIndexer->isIndexerScheduled()) {
            $isChangedProductList = $category->getData('is_changed_product_list');

            if ($isChangedProductList) {
                // happens when admin edits a category adding or removing items in "Products in Category" list
                $this->productIndexer->reindexList($category->getAffectedProductIds());
            }
        }
    }
}

<?php

namespace StreamX\ConnectorCatalog\Plugin\Indexer\Category\Save;

use StreamX\ConnectorCatalog\Model\Indexer\CategoryProcessor;
use StreamX\ConnectorCatalog\Model\ResourceModel\Category as CategoryResourceModel;

use Magento\Catalog\Model\Category;

class UpdateCategoryDataPlugin
{
    /**
     * @var CategoryResourceModel
     */
    private $resourceModel;

    /**
     * @var CategoryProcessor
     */
    private $categoryProcessor;

    public function __construct(
        CategoryResourceModel $resourceModel,
        CategoryProcessor $processor
    ) {
        $this->categoryProcessor = $processor;
        $this->resourceModel = $resourceModel;
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
    }
}

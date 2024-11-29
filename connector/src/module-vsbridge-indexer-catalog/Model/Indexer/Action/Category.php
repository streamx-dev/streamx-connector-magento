<?php

namespace Divante\VsbridgeIndexerCatalog\Model\Indexer\Action;

use Divante\VsbridgeIndexerCatalog\Model\ResourceModel\Category as ResourceModel;

class Category
{
    /**
     * @var ResourceModel
     */
    private $resourceModel;

    public function __construct(ResourceModel $resourceModel)
    {
        $this->resourceModel = $resourceModel;
    }

    /**
     * @param int $storeId
     *
     * @return \Generator
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function rebuild($storeId = 1, array $categoryIds = [])
    {
        $lastCategoryId = 0;

        if (!empty($categoryIds)) {
            $categoryIds = $this->resourceModel->getParentIds($categoryIds);
        }

        do {
            $categories = $this->resourceModel->getCategories($storeId, $categoryIds, $lastCategoryId);

            foreach ($categories as $category) {
                $lastCategoryId = $category['entity_id'];
                $categoryData['id'] = (int)$category['entity_id'];
                $categoryData = $category;

                yield $lastCategoryId => $categoryData;
            }
        } while (!empty($categories));
    }
}

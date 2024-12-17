<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\Action;

use Generator;
use StreamX\ConnectorCatalog\Model\ResourceModel\Category as ResourceModel;

class Category {
    private ResourceModel $resourceModel;

    public function __construct(ResourceModel $resourceModel) {
        $this->resourceModel = $resourceModel;
    }

    /**
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function rebuild(int $storeId = 1, array $categoryIds = []): Generator {
        $lastCategoryId = 0;

        // Ensure to reindex also the parents category ids
        if (!empty($categoryIds)) {
            $categoryIds = $this->withParentIds($categoryIds);
        }

        // 1. Publish edited and added categories
        $publishedCategoryIds = [];
        do {
            $categories = $this->resourceModel->getCategories($storeId, $categoryIds, $lastCategoryId);

            foreach ($categories as $category) {
                $lastCategoryId = $category['entity_id'];
                $categoryData['id'] = (int)$category['entity_id'];
                $categoryData = $category;

                yield $lastCategoryId => $categoryData;
                $publishedCategoryIds[] = $lastCategoryId;
            }
        } while (!empty($categories));

        // 2. Unpublish deleted categories
        $idsOfCategoriesToUnpublish = array_diff($categoryIds, $publishedCategoryIds);
        foreach ($idsOfCategoriesToUnpublish as $categoryId) {
            yield $categoryId => ['id' => $categoryId];
        }
    }

    private function withParentIds(array $categoryIds): array {
        $parentIds = $this->resourceModel->getParentIds($categoryIds);
        return array_unique(array_merge($categoryIds, $parentIds));
    }
}

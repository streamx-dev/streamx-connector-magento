<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Indexer\Action;

use Exception;
use Magento\Framework\Exception\NoSuchEntityException;
use StreamX\ConnectorCatalog\Model\ResourceModel\Category as ResourceModel;
use Traversable;

class Category implements BaseAction {

    private ResourceModel $resourceModel;

    public function __construct(ResourceModel $resourceModel) {
        $this->resourceModel = $resourceModel;
    }

    /**
     * @throws Exception
     * @throws NoSuchEntityException
     */
    public function loadData(int $storeId, array $categoryIds): Traversable {
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
                $lastCategoryId = (int) $category['id'];
                yield $lastCategoryId => $category;
                $publishedCategoryIds[] = $lastCategoryId;
            }
        } while (!empty($categories));

        // 2. Unpublish deleted categories
        $idsOfCategoriesToUnpublish = array_diff($categoryIds, $publishedCategoryIds);
        foreach ($idsOfCategoriesToUnpublish as $categoryId) {
            yield $categoryId => [];
        }
    }

    private function withParentIds(array $categoryIds): array {
        $parentIds = $this->resourceModel->getParentIds($categoryIds);
        return array_unique(array_merge($categoryIds, $parentIds));
    }
}

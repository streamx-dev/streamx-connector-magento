<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Indexer\DataLoader;

use Exception;
use Magento\Framework\Exception\NoSuchEntityException;
use StreamX\ConnectorCatalog\Model\ResourceModel\Category as ResourceModel;
use StreamX\ConnectorCore\Api\BasicDataLoader;
use Traversable;

class CategoryDataLoader implements BasicDataLoader {

    private ResourceModel $resourceModel;

    public function __construct(ResourceModel $resourceModel) {
        $this->resourceModel = $resourceModel;
    }

    /**
     * @inheritdoc
     * @throws Exception
     * @throws NoSuchEntityException
     */
    public function loadData(int $storeId, array $categoryIds): Traversable {
        $lastCategoryId = 0;

        // Ensure to reindex also all the parents categories (including grandparents etc.)
        if (!empty($categoryIds)) {
            $this->addParentIds($categoryIds);
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
            yield $categoryId => null;
        }
    }

    private function addParentIds(array &$categoryIds): void {
        $parentIds = $this->resourceModel->getParentIds($categoryIds);
        $categoryIds = array_unique(array_merge($categoryIds, $parentIds));
    }
}

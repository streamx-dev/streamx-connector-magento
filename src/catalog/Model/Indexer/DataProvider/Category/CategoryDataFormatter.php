<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Category;

use StreamX\ConnectorCatalog\Model\ResourceModel\Category\Children as CategoryChildrenResource;
use StreamX\ConnectorCatalog\Model\SlugGenerator;
use StreamX\ConnectorCore\Api\DataProviderInterface;

/**
 * Adds subcategories data and formats all as categories tree
 */
class CategoryDataFormatter implements DataProviderInterface
{
    private const ID = 'id';
    private const NAME = 'name';
    private const LABEL = 'label';
    private const SLUG = 'slug';
    private const SUBCATEGORIES = 'subcategories';
    private const PARENT = 'parent';

    private CategoryChildrenResource $childrenResourceModel;
    private SlugGenerator $slugGenerator;

    public function __construct(
        CategoryChildrenResource $childrenResource,
        SlugGenerator $slugGenerator
    ) {
        $this->childrenResourceModel = $childrenResource;
        $this->slugGenerator = $slugGenerator;
    }

    public function addData(array &$indexData, int $storeId): void
    {
        foreach ($indexData as &$categoryData) {
            $this->prepareCategory($categoryData);

            $children = $this->childrenResourceModel->loadChildren($categoryData['path'], $storeId);
            $groupedChildrenById = $this->groupChildrenById($children);
            $this->addChildrenData($categoryData, $groupedChildrenById, $storeId);

            $this->removeUnnecessaryFields($categoryData);
        }
    }

    private function removeUnnecessaryFields(array &$category): void
    {
        unset($category['url_key'], $category['path'], $category['parent_id']);
        if (isset($category[self::PARENT])) {
            $this->removeUnnecessaryFields($category[self::PARENT]);
            $this->moveParentToBottom($category);
        }
        if (isset($category[self::SUBCATEGORIES])) {
            foreach ($category[self::SUBCATEGORIES] as &$subcategory) {
                unset($subcategory['url_key'], $subcategory['path'], $subcategory['parent_id']);
            }
        }
    }

    private function addChildrenData(array &$category, array $groupedChildren, int $storeId): void
    {
        $categoryId = $category[self::ID];
        $childrenData = $this->plotTree($groupedChildren, $categoryId, $storeId);

        $category[self::SUBCATEGORIES] = $childrenData;
    }

    private function groupChildrenById(array $children): array
    {
        $sortChildrenById = [];

        foreach ($children as $cat) {
            $sortChildrenById[$cat['id']] = $cat;
            $sortChildrenById[$cat['id']][self::SUBCATEGORIES] = [];
        }

        return $sortChildrenById;
    }

    private function plotTree(array $categories, int $rootId, int $storeId): array
    {
        $categoryTree = [];

        foreach ($categories as $categoryId => $categoryData) {
            $parent = $categoryData['parent_id'];

            # A direct child is found
            if ($parent == $rootId) {
                # Remove item from tree (we don't need to traverse this again)
                unset($categories[$categoryId]);

                $this->prepareCategory($categoryData);
                $categoryData[self::SUBCATEGORIES] = $this->plotTree($categories, $categoryId, $storeId);
                $categoryTree[] = $categoryData;
            }
        }

        return empty($categoryTree) ? [] : $categoryTree;
    }

    private function prepareCategory(array &$categoryData): void
    {
        $categoryData[self::ID] = (int) $categoryData['id'];
        $categoryData[self::SLUG] = $this->computeSlug($categoryData);
        $categoryData[self::LABEL] = $categoryData[self::NAME];
        if (isset($categoryData[self::PARENT])) {
            $this->prepareCategory($categoryData[self::PARENT]);
        }
    }

    private function moveParentToBottom(array &$categoryData): void
    {
        $parent = $categoryData[self::PARENT];
        unset($categoryData[self::PARENT]);
        $categoryData[self::PARENT] = $parent;
    }

    function computeSlug(array $categoryDTO): string
    {
        return $this->slugGenerator->compute($categoryDTO);
    }
}

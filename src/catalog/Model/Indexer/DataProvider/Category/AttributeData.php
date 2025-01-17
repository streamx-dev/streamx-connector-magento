<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Category;

use StreamX\ConnectorCatalog\Model\ResourceModel\Category\Children as CategoryChildrenResource;
use StreamX\ConnectorCatalog\Model\Category\ComputeCategorySlug;
use StreamX\ConnectorCore\Api\DataProviderInterface;

class AttributeData implements DataProviderInterface
{
    // TODO convert to DTO class
    private const ID = 'id';
    private const NAME = 'name';
    private const LABEL = 'label';
    private const SLUG = 'slug';
    private const SUBCATEGORIES = 'subcategories';

    // TODO: transform parentCategoryId to parent category (full, with own parents, without children)
    private const PARENT_CATEGORY_ID = 'parentCategoryId';

    private CategoryChildrenResource $childrenResourceModel;
    private ComputeCategorySlug $computeCategorySlug;

    public function __construct(
        CategoryChildrenResource $childrenResource,
        ComputeCategorySlug $computeCategorySlug
    ) {
        $this->computeCategorySlug = $computeCategorySlug;
        $this->childrenResourceModel = $childrenResource;
    }

    public function addData(array $indexData, int $storeId): array
    {
        // TODO: load all categories first, to then be able to set parent category for each of the published categories

        foreach ($indexData as $categoryId => $categoryData) {
            $categoryData = $this->prepareCategory($categoryData);
            $children = $this->childrenResourceModel->loadChildren($categoryData, $storeId);
            $groupedChildrenById = $this->groupChildrenById($children);
            unset($children);

            $indexData[$categoryId] = $this->addChildrenData($categoryData, $groupedChildrenById, $storeId);
        }

        $this->removeUnnecessaryFields($indexData);

        return $indexData;
    }

    private function removeUnnecessaryFields(array &$categories): void
    {
        foreach ($categories as &$category) {
            unset($category['url_key'], $category['path'], $category['parent_id']);
            $this->removeUnnecessaryFields($category[self::SUBCATEGORIES]);
        }
    }

    private function addChildrenData(array $category, array $groupedChildren, int $storeId): array
    {
        $categoryId = $category[self::ID];
        $childrenData = $this->plotTree($groupedChildren, $categoryId, $storeId);

        $category[self::SUBCATEGORIES] = $childrenData;

        return $category;
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

                $categoryData = $this->prepareCategory($categoryData);
                $categoryData[self::SUBCATEGORIES] = $this->plotTree($categories, $categoryId, $storeId);
                $categoryTree[] = $categoryData;
            }
        }

        return empty($categoryTree) ? [] : $categoryTree;
    }

    private function prepareCategory(array $categoryData): array
    {
        $categoryData[self::ID] = (int) $categoryData['id'];
        $categoryData[self::PARENT_CATEGORY_ID] = (int) $categoryData['parent_id'];
        $categoryData[self::SLUG] = $this->computeSlug($categoryData);
        $categoryData[self::LABEL] = $categoryData[self::NAME];

        return $categoryData;
    }

    private function computeSlug(array $categoryDTO): string
    {
        return $this->computeCategorySlug->compute($categoryDTO);
    }
}

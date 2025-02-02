<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Category;

use StreamX\ConnectorCatalog\Model\ResourceModel\Category as CategoryResource;
use StreamX\ConnectorCatalog\Model\ResourceModel\Category\Children as CategoryChildrenResource;
use StreamX\ConnectorCatalog\Model\SlugGenerator;
use StreamX\ConnectorCore\Api\DataProviderInterface;

class CategoryDataFormatter extends DataProviderInterface
{
    // TODO convert to DTO class
    private const ID = 'id';
    private const NAME = 'name';
    private const LABEL = 'label';
    private const SLUG = 'slug';
    private const SUBCATEGORIES = 'subcategories';
    private const PARENT = 'parent';

    private CategoryResource $resourceModel;
    private CategoryChildrenResource $childrenResourceModel;
    private SlugGenerator $slugGenerator;

    public function __construct(
        CategoryResource $resource,
        CategoryChildrenResource $childrenResource,
        SlugGenerator $slugGenerator
    ) {
        $this->resourceModel = $resource;
        $this->childrenResourceModel = $childrenResource;
        $this->slugGenerator = $slugGenerator;
    }

    public function addData(array $indexData, int $storeId): array
    {
        return $this->formatCategoriesAsTree($indexData, $storeId);
    }

    public function formatCategoriesAsTree(array $categoriesData, int $storeId): array {
        foreach ($categoriesData as &$categoryData) {
            $this->prepareCategory($categoryData);
            $children = $this->childrenResourceModel->loadChildren($categoryData, $storeId);
            $groupedChildrenById = $this->groupChildrenById($children);
            unset($children);

            $this->addChildrenData($categoryData, $groupedChildrenById, $storeId);
        }

        $allCategoriesMap = $this->getAllCategoriesMap($storeId);
        $this->setParentCategory($categoriesData, $allCategoriesMap);
        $this->removeUnnecessaryFieldsRecursively($categoriesData);

        return $categoriesData;
    }

    private function removeUnnecessaryFieldsRecursively(array &$categories): void
    {
        foreach ($categories as &$category) {
            $this->removeUnnecessaryFields($category);
            if (isset($category[self::PARENT])) {
                $this->removeUnnecessaryFields($category[self::PARENT]);
            }
            $this->removeUnnecessaryFieldsRecursively($category[self::SUBCATEGORIES]);
        }
    }

    private function removeUnnecessaryFields(array &$category): void
    {
        unset($category['url_key'], $category['path'], $category['parent_id']);
    }

    private function setParentCategory(array &$categories, array $allCategoriesMap): void
    {
        foreach ($categories as &$category) {
            $parentCategoryId = $category['parent_id'];
            if (isset($allCategoriesMap[$parentCategoryId])) { // root category may not be present in the results, so leave parent as null
                $parentCategory = $allCategoriesMap[$parentCategoryId];
                $this->prepareCategory($parentCategory);
                $category[self::PARENT] = $parentCategory;
            }
            $this->setParentCategory($category[self::SUBCATEGORIES], $allCategoriesMap);
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
    }

    function computeSlug(array $categoryDTO): string
    {
        return $this->slugGenerator->compute($categoryDTO);
    }

    private function getAllCategoriesMap(int $storeId): array
    {
        $allCategories = $this->resourceModel->getCategories($storeId);

        $allCategoriesMap = [];
        foreach ($allCategories as $category) {
            $allCategoriesMap[$category['id']] = $category;
        }
        return $allCategoriesMap;
    }
}

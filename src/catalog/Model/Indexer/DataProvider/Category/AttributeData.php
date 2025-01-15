<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Category;

use StreamX\ConnectorCatalog\Model\ResourceModel\Category\Children as CategoryChildrenResource;
use StreamX\ConnectorCore\Indexer\DataFilter;
use StreamX\ConnectorCatalog\Api\ComputeCategorySlugInterface;
use StreamX\ConnectorCatalog\Model\ResourceModel\Category\AttributeDataProvider;
use StreamX\ConnectorCore\Api\DataProviderInterface;

class AttributeData implements DataProviderInterface
{
    // TODO convert to DTO class
    private const ID = 'id';
    private const SLUG = 'slug';
    private const SUBCATEGORIES = 'subcategories';
    private const PARENT_CATEGORY_ID = 'parentCategoryId';

    private array $requiredAttributes = [
        'name',
        'url_key',
        'url_path'
    ];

    private array $fieldsToRemove = [
        'parent_id',
        'position',
        'level',
        'children_count',
        'row_id',
        'created_in',
        'updated_in',
        'entity_id',
        'entity_type_id',
        'attribute_set_id',
        'all_children',
        'created_at',
        'updated_at',
        'request_path',
    ];

    private AttributeDataProvider $attributeResourceModel;
    private CategoryChildrenResource $childrenResourceModel;
    private DataFilter $dataFilter;
    private ComputeCategorySlugInterface $computeCategorySlug;

    public function __construct(
        AttributeDataProvider $attributeResource,
        CategoryChildrenResource $childrenResource,
        ComputeCategorySlugInterface $computeCategorySlug,
        DataFilter $dataFilter
    ) {
        $this->computeCategorySlug = $computeCategorySlug;
        $this->attributeResourceModel = $attributeResource;
        $this->childrenResourceModel = $childrenResource;
        $this->dataFilter = $dataFilter;
    }

    public function addData(array $indexData, int $storeId): array
    {
        $categoryIds = array_keys($indexData);
        $attributes = $this->attributeResourceModel->loadAttributesData(
            $storeId,
            $categoryIds,
            $this->requiredAttributes
        );

        foreach ($attributes as $entityId => $attributesData) {
            $categoryData = array_merge($indexData[$entityId], $attributesData);
            $categoryData = $this->prepareCategory($categoryData);

            $indexData[$entityId] = $categoryData;
        }

        foreach ($indexData as $categoryId => $categoryData) {
            $children = $this->childrenResourceModel->loadChildren($categoryData, $storeId);
            $groupedChildrenById = $this->groupChildrenById($children);
            unset($children);

            $childrenRowAttributes =
                $this->attributeResourceModel->loadAttributesData(
                    $storeId,
                    array_keys($groupedChildrenById),
                    $this->requiredAttributes
                );
            $indexData[$categoryId] = $this->addChildrenData($categoryData, $groupedChildrenById, $childrenRowAttributes, $storeId);
        }

        $this->removeUnnecessaryFields($indexData);

        return $indexData;
    }

    private function removeUnnecessaryFields(array &$categories): void
    {
        foreach ($categories as &$category) {
            unset($category['url_path'], $category['url_key'], $category['path']);

            if (!empty($category[self::SUBCATEGORIES])) {
                $this->removeUnnecessaryFields($category[self::SUBCATEGORIES]);
            }
        }
    }

    private function addChildrenData(array $category, array $groupedChildren, array $childrenRowAttributes, int $storeId): array
    {
        $categoryId = $category[self::ID];
        $childrenData = $this->plotTree($groupedChildren, $childrenRowAttributes, $categoryId, $storeId);

        $category[self::SUBCATEGORIES] = $childrenData;

        return $category;
    }

    private function groupChildrenById(array $children): array
    {
        $sortChildrenById = [];

        foreach ($children as $cat) {
            $sortChildrenById[$cat['entity_id']] = $cat;
            $sortChildrenById[$cat['entity_id']][self::SUBCATEGORIES] = [];
        }

        return $sortChildrenById;
    }

    private function plotTree(array $categories, array $childrenRowAttributes, int $rootId, int $storeId): array
    {
        $categoryTree = [];

        foreach ($categories as $categoryId => $categoryData) {
            $parent = $categoryData['parent_id'];

            # A direct child is found
            if ($parent == $rootId) {
                # Remove item from tree (we don't need to traverse this again)
                unset($categories[$categoryId]);

                if (isset($childrenRowAttributes[$categoryId])) {
                    $categoryData = array_merge($categoryData, $childrenRowAttributes[$categoryId]);
                }

                $categoryData = $this->prepareCategory($categoryData);
                $categoryData[self::SUBCATEGORIES] = $this->plotTree($categories, $childrenRowAttributes, $categoryId, $storeId);
                $categoryTree[] = $categoryData;
            }
        }

        return empty($categoryTree) ? [] : $categoryTree;
    }

    private function prepareCategory(array $categoryData): array
    {
        $categoryData[self::ID] = (int)$categoryData['entity_id'];
        $categoryData[self::PARENT_CATEGORY_ID] = (int)$categoryData['parent_id'];
        $categoryData[self::SLUG] = $this->computeSlug($categoryData);

        $categoryData = array_diff_key($categoryData, array_flip($this->fieldsToRemove));
        $categoryData = $this->filterData($categoryData);

        return $categoryData;
    }

    private function computeSlug(array $categoryDTO): string
    {
        return $this->computeCategorySlug->compute($categoryDTO);
    }

    private function filterData(array $categoryData): array
    {
        return $this->dataFilter->execute($categoryData);
    }
}

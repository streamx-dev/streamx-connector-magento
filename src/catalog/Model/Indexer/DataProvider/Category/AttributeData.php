<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Category;

use StreamX\ConnectorCatalog\Model\ResourceModel\Category\Children as CategoryChildrenResource;
use StreamX\ConnectorCore\Indexer\DataFilter;
use StreamX\ConnectorCatalog\Model\SystemConfig\CategoryConfigInterface;
use StreamX\ConnectorCatalog\Api\ApplyCategorySlugInterface;
use StreamX\ConnectorCatalog\Model\ResourceModel\Category\AttributeDataProvider;
use StreamX\ConnectorCatalog\Model\ResourceModel\Category\ProductCount as ProductCountResourceModel;
use StreamX\ConnectorCatalog\Api\DataProvider\Category\AttributeDataProviderInterface;

class AttributeData implements AttributeDataProviderInterface
{
    /**
     * List of fields from category
     */
    private array $fieldsToRemove = [
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
    private ProductCountResourceModel $productCountResource;
    private DataFilter $dataFilter;
    private array $childrenRowAttributes = [];
    private array $childrenProductCount = [];
    private CategoryConfigInterface $settings;
    private ApplyCategorySlugInterface $applyCategorySlug;

    public function __construct(
        AttributeDataProvider $attributeResource,
        CategoryChildrenResource $childrenResource,
        ProductCountResourceModel $productCountResource,
        ApplyCategorySlugInterface $applyCategorySlug,
        CategoryConfigInterface $configSettings,
        DataFilter $dataFilter
    ) {
        $this->settings = $configSettings;
        $this->applyCategorySlug = $applyCategorySlug;
        $this->productCountResource = $productCountResource;
        $this->attributeResourceModel = $attributeResource;
        $this->childrenResourceModel = $childrenResource;
        $this->dataFilter = $dataFilter;
    }

    public function addData(array $indexData, int $storeId): array
    {
        $this->settings->getAttributesUsedForSortBy();

        // There is no option yet to load only specific categories
        $categoryIds = array_keys($indexData);
        $attributes = $this->attributeResourceModel->loadAttributesData(
            $storeId,
            $categoryIds
        );
        $productCount = $this->productCountResource->loadProductCount($categoryIds);

        foreach ($attributes as $entityId => $attributesData) {
            $categoryData = array_merge($indexData[$entityId], $attributesData);
            $categoryData = $this->prepareParentCategory($categoryData, $storeId);
            $categoryData = $this->addDefaultSortByOption($categoryData, $storeId);
            $categoryData = $this->addAvailableSortByOption($categoryData, $storeId);
            $categoryData['product_count'] = $productCount[$entityId];

            $indexData[$entityId] = $categoryData;
        }

        foreach ($indexData as $categoryId => $categoryData) {
            $children = $this->childrenResourceModel->loadChildren($categoryData, $storeId);
            $groupedChildrenById = $this->groupChildrenById($children);
            unset($children);

            $this->childrenRowAttributes =
                $this->attributeResourceModel->loadAttributesData(
                    $storeId,
                    array_keys($groupedChildrenById)
                );

            $this->childrenProductCount = $this->productCountResource->loadProductCount(
                array_keys($groupedChildrenById)
            );
            $indexData[$categoryId] = $this->addChildrenData($categoryData, $groupedChildrenById, $storeId);
        }

        return $indexData;
    }

    private function addChildrenData(array $category, array $groupedChildren, int $storeId): array
    {
        $categoryId = $category['id'];
        $childrenData = $this->plotTree($groupedChildren, $categoryId, $storeId);

        $category['children_data'] = $childrenData;
        $category['children_count'] = count($childrenData);

        return $category;
    }

    private function groupChildrenById(array $children): array
    {
        $sortChildrenById = [];

        foreach ($children as $cat) {
            $sortChildrenById[$cat['entity_id']] = $cat;
            $sortChildrenById[$cat['entity_id']]['children_data'] = [];
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

                if (isset($this->childrenRowAttributes[$categoryId])) {
                    $categoryData = array_merge($categoryData, $this->childrenRowAttributes[$categoryId]);
                }

                $categoryData['product_count'] = $this->childrenProductCount[$categoryId];
                $categoryData = $this->prepareChildCategory($categoryData, $storeId);
                $categoryData['children_data'] = $this->plotTree($categories, $categoryId, $storeId);
                $categoryData['children_count'] = count($categoryData['children_data']);
                $categoryTree[] = $categoryData;
            }
        }

        return empty($categoryTree) ? [] : $categoryTree;
    }

    public function prepareParentCategory(array $categoryDTO, int $storeId): array
    {
        return $this->prepareCategory($categoryDTO, $storeId);
    }

    public function prepareChildCategory(array $categoryDTO, int $storeId): array
    {
        return $this->prepareCategory($categoryDTO, $storeId);
    }

    private function prepareCategory(array $categoryDTO, int $storeId): array
    {
        $categoryDTO['id'] = (int)$categoryDTO['entity_id'];

        $categoryDTO = $this->addSlug($categoryDTO);

        if (!isset($categoryDTO['url_path'])) {
            $categoryDTO['url_path'] = $categoryDTO['slug'];
        } else {
            $categoryDTO['url_path'] .= $this->settings->getCategoryUrlSuffix($storeId);
        }

        $categoryDTO = array_diff_key($categoryDTO, array_flip($this->fieldsToRemove));
        $categoryDTO = $this->filterData($categoryDTO);

        return $categoryDTO;
    }

    private function addAvailableSortByOption(array $category, int $storeId): array
    {
        if (isset($category['available_sort_by'])) {
            return $category;
        }

        $category['available_sort_by'] = $this->settings->getAttributesUsedForSortBy();

        return $category;
    }

    private function addDefaultSortByOption(array $category, int $storeId): array
    {
        if (isset($category['default_sort_by'])) {
            return $category;
        }

        $category['default_sort_by'] = $this->settings->getProductListDefaultSortBy($storeId);

        return $category;
    }

    private function addSlug(array $categoryDTO): array
    {
        return $this->applyCategorySlug->execute($categoryDTO);
    }

    private function filterData(array $categoryData): array
    {
        return $this->getDataFilter()->execute($categoryData);
    }

    private function getDataFilter(): DataFilter
    {
        return $this->dataFilter;
    }
}

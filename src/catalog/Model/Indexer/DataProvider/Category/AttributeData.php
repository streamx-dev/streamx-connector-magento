<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Category;

use StreamX\ConnectorCatalog\Model\ResourceModel\Category\Children as CategoryChildrenResource;
use StreamX\ConnectorCore\Indexer\DataFilter;
use StreamX\ConnectorCatalog\Api\ApplyCategorySlugInterface;
use StreamX\ConnectorCatalog\Model\ResourceModel\Category\AttributeDataProvider;
use StreamX\ConnectorCatalog\Model\ResourceModel\Category\ProductCount as ProductCountResourceModel;
use StreamX\ConnectorCore\Api\DataProviderInterface;

class AttributeData implements DataProviderInterface
{
    /**
     * List of fields from category
     */
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
    private ProductCountResourceModel $productCountResource;
    private DataFilter $dataFilter;
    private array $childrenRowAttributes = [];
    private array $childrenProductCount = [];
    private ApplyCategorySlugInterface $applyCategorySlug;

    public function __construct(
        AttributeDataProvider $attributeResource,
        CategoryChildrenResource $childrenResource,
        ProductCountResourceModel $productCountResource,
        ApplyCategorySlugInterface $applyCategorySlug,
        DataFilter $dataFilter
    ) {
        $this->applyCategorySlug = $applyCategorySlug;
        $this->productCountResource = $productCountResource;
        $this->attributeResourceModel = $attributeResource;
        $this->childrenResourceModel = $childrenResource;
        $this->dataFilter = $dataFilter;
    }

    public function addData(array $indexData, int $storeId): array
    {
        // There is no option yet to load only specific categories
        $categoryIds = array_keys($indexData);
        $attributes = $this->attributeResourceModel->loadAttributesData(
            $storeId,
            $categoryIds,
            ['name', 'url_key', 'url_path']
        );
        $productCount = $this->productCountResource->loadProductCount($categoryIds);

        foreach ($attributes as $entityId => $attributesData) {
            $categoryData = array_merge($indexData[$entityId], $attributesData);
            $categoryData = $this->prepareCategory($categoryData, $storeId);
            $categoryData['productCount'] = $productCount[$entityId];

            $indexData[$entityId] = $categoryData;
        }

        foreach ($indexData as $categoryId => $categoryData) {
            $children = $this->childrenResourceModel->loadChildren($categoryData, $storeId);
            $groupedChildrenById = $this->groupChildrenById($children);
            unset($children);

            $this->childrenRowAttributes =
                $this->attributeResourceModel->loadAttributesData(
                    $storeId,
                    array_keys($groupedChildrenById),
                    ['name', 'url_key', 'url_path']
                );

            $this->childrenProductCount = $this->productCountResource->loadProductCount(
                array_keys($groupedChildrenById)
            );
            $indexData[$categoryId] = $this->addChildrenData($categoryData, $groupedChildrenById, $storeId);
        }

        $this->cleanup($indexData);

        return $indexData;
    }

    function cleanup(array &$categories) {
        foreach ($categories as &$category) {
            unset($category['url_path'], $category['url_key'], $category['path']);

            if (!empty($category['subcategories'])) {
                $this->cleanup($category['subcategories']);
            }
        }
    }

    private function addChildrenData(array $category, array $groupedChildren, int $storeId): array
    {
        $categoryId = $category['id'];
        $childrenData = $this->plotTree($groupedChildren, $categoryId, $storeId);

        $category['subcategories'] = $childrenData;
        $category['subcategoriesCount'] = count($childrenData);

        return $category;
    }

    private function groupChildrenById(array $children): array
    {
        $sortChildrenById = [];

        foreach ($children as $cat) {
            $sortChildrenById[$cat['entity_id']] = $cat;
            $sortChildrenById[$cat['entity_id']]['subcategories'] = [];
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

                $categoryData['productCount'] = $this->childrenProductCount[$categoryId];
                $categoryData = $this->prepareCategory($categoryData, $storeId);
                $categoryData['subcategories'] = $this->plotTree($categories, $categoryId, $storeId);
                $categoryData['subcategoriesCount'] = count($categoryData['subcategories']);
                $categoryTree[] = $categoryData;
            }
        }

        return empty($categoryTree) ? [] : $categoryTree;
    }

    private function prepareCategory(array $categoryDTO, int $storeId): array
    {
        $categoryDTO['id'] = (int)$categoryDTO['entity_id'];
        $categoryDTO['parentCategoryId'] = (int)$categoryDTO['parent_id'];

        $categoryDTO = $this->addSlug($categoryDTO);

        $categoryDTO = array_diff_key($categoryDTO, array_flip($this->fieldsToRemove));
        $categoryDTO = $this->filterData($categoryDTO);

        return $categoryDTO;
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

<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Category\CategoryDataFormatter;
use StreamX\ConnectorCore\Api\DataProviderInterface;
use StreamX\ConnectorCatalog\Model\ResourceModel\Category as CategoryResource;

class CategoryData implements DataProviderInterface
{
    private CategoryResource $categoryResource;
    private CategoryDataFormatter $categoryDataFormatter;

    public function __construct(
        CategoryResource $categoryResource,
        CategoryDataFormatter $categoryDataFormatter
    ) {
        $this->categoryResource = $categoryResource;
        $this->categoryDataFormatter = $categoryDataFormatter;
    }

    /**
     * @inheritdoc
     */
    public function addData(array &$indexData, int $storeId): void
    {
        // 1. load map of: productId -> its categoryIds
        $productIds = array_keys($indexData);
        $productCategoriesMap = $this->categoryResource->getProductCategoriesMap($storeId, $productIds);

        // 2. load category data of all categories
        $categoryIds = $this->extractCategoryIds($productCategoriesMap);
        $categoryData = $this->categoryResource->getCategories($storeId, $categoryIds);

        // 3. format each category as tree with subcategories and parents
        $this->categoryDataFormatter->formatCategoriesAsTree($categoryData, $storeId);

        // 4. add formatted categories data to products
        foreach ($indexData as $productId => &$productData) {
            $productData['categories'] = [];
            if (isset($productCategoriesMap[$productId])) {
                $productCategoryIds = $productCategoriesMap[$productId];
                foreach ($categoryData as $category) {
                    if (in_array($category['id'], $productCategoryIds)) {
                        $productData['categories'][] = $category;
                    }
                }
            }
        }
    }

    private function extractCategoryIds(array $productCategoriesMap): array
    {
        $categoryIds = [];
        foreach ($productCategoriesMap as $productCategoryIds) {
            $categoryIds = array_unique(array_merge($categoryIds, $productCategoryIds));
        }
        return $categoryIds;
    }
}

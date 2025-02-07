<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Category\CategoryDataFormatter;
use StreamX\ConnectorCore\Api\DataProviderInterface;
use StreamX\ConnectorCatalog\Model\ResourceModel\Category as CategoryResource;

class CategoryData extends DataProviderInterface
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
    public function addData(array $indexData, int $storeId): array
    {
        // 1. load map of: productId -> its categoryIds
        $productIds = array_keys($indexData);
        $productCategoriesMap = $this->categoryResource->getProductCategoriesMap($storeId, $productIds);

        // 2. load category data of all categories
        $categoryIds = $this->extractCategoryIds($productCategoriesMap);
        $categoryData = $this->categoryResource->getCategories($storeId, $categoryIds);

        // 3. format each category as tree with subcategories and parents
        $formattedCategories = $this->categoryDataFormatter->formatCategoriesAsTree($categoryData, $storeId);

        // 4. add formatted categories data to products
        foreach ($indexData as $productId => &$productData) {
            $productCategoryIds = $productCategoriesMap[$productId];
            foreach ($formattedCategories as $formattedCategory) {
                if (in_array($formattedCategory['id'], $productCategoryIds)) {
                    $productData['categories'][] = $formattedCategory;
                }
            }
        }

        return $indexData;
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

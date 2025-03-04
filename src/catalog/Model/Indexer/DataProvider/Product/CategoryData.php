<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use StreamX\ConnectorCatalog\Model\SlugGenerator;
use StreamX\ConnectorCore\Api\DataProviderInterface;
use StreamX\ConnectorCatalog\Model\ResourceModel\Category as CategoryResource;

class CategoryData implements DataProviderInterface
{
    private CategoryResource $categoryResource;
    private SlugGenerator $slugGenerator;

    public function __construct(
        CategoryResource $categoryResource,
        SlugGenerator $slugGenerator
    ) {
        $this->categoryResource = $categoryResource;
        $this->slugGenerator = $slugGenerator;
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
        $categoryIds = array_unique(array_merge(...array_values($productCategoriesMap)));
        $categoryData = $this->categoryResource->getCategories($storeId, $categoryIds);

        // 3. format each category
        $this->adjustCategoriesFormat($categoryData);

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

    private function adjustCategoriesFormat(array &$categories): void
    {
        foreach ($categories as &$category) {
            $category['id'] = (int)$category['id'];
            $category['slug'] = $this->slugGenerator->compute($category);
            $category['label'] = $category['name'];
            unset(
                $category['url_key'],
                $category['path'],
                $category['parent_id']
            );
        }
    }
}

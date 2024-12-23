<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use StreamX\ConnectorCore\Api\DataProviderInterface;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\Category as Resource;

class CategoryData implements DataProviderInterface
{
    private Resource $resourceModel;

    public function __construct(Resource $resource)
    {
        $this->resourceModel = $resource;
    }

    /**
     * @inheritdoc
     */
    public function addData(array $indexData, int $storeId): array
    {
        $categoryData = $this->resourceModel->loadCategoryData($storeId, array_keys($indexData));

        foreach ($categoryData as $categoryDataRow) {
            $productId = (int)$categoryDataRow['product_id'];
            $categoryId = (int)$categoryDataRow['category_id'];
            $position = (int)$categoryDataRow['position'];

            $productCategoryData = [
                'category_id' => $categoryId,
                'name' => (string)$categoryDataRow['name'],
                'position' => $position,
            ];

            if (!isset($indexData[$productId]['category'])) {
                $indexData[$productId]['category'] = [];
            }

            if (!isset($indexData[$productId]['category_ids'])) {
                $indexData[$productId]['category_ids'] = [];
            }

            $indexData[$productId]['category'][] = $productCategoryData;
            $indexData[$productId]['category_ids'][] = $categoryId;
        }

        $categoryData = null;

        return $indexData;
    }
}

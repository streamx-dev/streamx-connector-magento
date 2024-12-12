<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\Action;

use StreamX\ConnectorCatalog\Model\ResourceModel\Product as ResourceModel;

class Product
{
    /**
     * @var ResourceModel
     */
    private $resourceModel;

    public function __construct(ResourceModel $resourceModel)
    {
        $this->resourceModel = $resourceModel;
    }

    /**
     * @return \Generator
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function rebuild(int $storeId = 1, array $productIds = [])
    {
        $lastProductId = 0;

        // Ensure to reindex also the parents product ids
        if (!empty($productIds)) {
            $productIds = $this->getProductIds($productIds);
        }

        $yieldedProductIds = [];

        // 1. Publish edited products (TODO verify if added products are also processed here)
        do {
            $products = $this->resourceModel->getProducts($storeId, $productIds, $lastProductId);

            /** @var array $product */
            foreach ($products as $product) {
                $lastProductId = (int)$product['entity_id'];
                $product['id'] = $lastProductId;

                $product['attribute_set_id'] = (int)$product['attribute_set_id'];
                $product['media_gallery'] = [];

                unset($product['required_options']);
                unset($product['has_options']);
                yield $lastProductId => $product;
                $yieldedProductIds[] = $lastProductId;
            }
        } while (!empty($products));

        // 2. Unpublish deleted products
        $unYieldedProductIds = array_diff($productIds, $yieldedProductIds);
        foreach ($unYieldedProductIds as $unYieldedProductId) {
            yield $lastProductId => ['id' => $unYieldedProductId];
        }
    }

    private function getProductIds(array $childrenIds): array
    {
        $parentIds = $this->resourceModel->getRelationsByChild($childrenIds);

        if (!empty($parentIds)) {
            $parentIds = array_map('intval', $parentIds);
        }

        return array_unique(array_merge($childrenIds, $parentIds));
    }
}

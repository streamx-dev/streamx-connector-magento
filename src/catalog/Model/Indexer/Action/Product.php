<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\Action;

use Generator;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product as ResourceModel;

class Product {
    private ResourceModel $resourceModel;

    public function __construct(ResourceModel $resourceModel) {
        $this->resourceModel = $resourceModel;
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function rebuild(int $storeId = 1, array $productIds = []): Generator {
        $lastProductId = 0;

        // Ensure to reindex also the parents product ids
        if (!empty($productIds)) {
            $productIds = $this->withParentIds($productIds);
        }

        // 1. Publish edited and added products
        $publishedProductIds = [];
        do {
            // TODO:
            $products = $this->resourceModel->getProducts($storeId, $productIds, $lastProductId);

            foreach ($products as $product) {
                $lastProductId = (int)$product['entity_id'];
                $product['id'] = $lastProductId;

                $product['attribute_set_id'] = (int)$product['attribute_set_id'];
                $product['media_gallery'] = [];

                unset($product['required_options']);
                unset($product['has_options']);
                yield $lastProductId => $product;
                $publishedProductIds[] = $lastProductId;
            }
        } while (!empty($products));

        // 2. Unpublish deleted products
        $idsOfProductsToUnpublish = array_diff($productIds, $publishedProductIds);
        foreach ($idsOfProductsToUnpublish as $productId) {
            yield $productId => ['id' => $productId];
        }
    }

    private function withParentIds(array $childrenIds): array {
        $parentIds = $this->resourceModel->getRelationsByChild($childrenIds);

        if (!empty($parentIds)) {
            $parentIds = array_map('intval', $parentIds);
        }

        return array_unique(array_merge($childrenIds, $parentIds));
    }
}

<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\Action;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product as ResourceModel;
use Traversable;

class Product implements BaseAction {

    private ResourceModel $resourceModel;

    public function __construct(ResourceModel $resourceModel) {
        $this->resourceModel = $resourceModel;
    }

    /**
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function loadData(int $storeId = 1, array $productIds = []): Traversable {
        $lastProductId = 0;

        // Ensure to reindex also the parents product ids
        if (!empty($productIds)) {
            $productIds = $this->withParentIds($productIds);
        }

        // 1. Publish edited and added products
        $publishedProductIds = [];
        do {
            $products = $this->resourceModel->getProducts($storeId, $productIds, $lastProductId);

            foreach ($products as $product) {
                $lastProductId = (int)$product['entity_id'];
                $product['id'] = $lastProductId;

                yield $lastProductId => $product;
                $publishedProductIds[] = $lastProductId;
            }
        } while (!empty($products));

        // 2. Unpublish deleted products
        $idsOfProductsToUnpublish = array_diff($productIds, $publishedProductIds);
        foreach ($idsOfProductsToUnpublish as $productId) {
            yield $productId => [];
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

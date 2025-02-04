<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\Action;

use Exception;
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

        if (!empty($productIds)) {
            // if a product is a child (variant) product - ensure to index only its parent, not the child product itself
            $productIds = $this->replaceProductVariantsWithTheirParents($productIds);

            // note: a simple product can only be a child of a single configurable product, but can be a child of multiple grouped or bundle products
            // TODO: verify what is published when a grouped product or its child is edited (expecting only parent with all children to be published)
            // TODO: verify what is published when a  bundle product or its child is edited (expecting only parent with all children to be published)
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

    /**
     * @param array<int> $productIds
     * @return array<int>
     * @throws Exception
     */
    public function replaceProductVariantsWithTheirParents(array $productIds): array
    {
        $childrenIdsWithParentIds = $this->resourceModel->getParentsForProductVariants($productIds);

        foreach ($childrenIdsWithParentIds as $childId => $parentIds) {
            // remove child product id
            $keyToUnset = array_search($childId, $productIds);
            unset($productIds[$keyToUnset]);

            // replace it with its parent id
            $productIds = array_merge($productIds, $parentIds);
        }

        return array_unique($productIds);
    }
}

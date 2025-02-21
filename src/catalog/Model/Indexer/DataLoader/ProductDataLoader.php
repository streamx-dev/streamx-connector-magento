<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Indexer\DataLoader;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product as ResourceModel;
use StreamX\ConnectorCore\Api\BasicDataLoader;
use Traversable;

class ProductDataLoader implements BasicDataLoader {

    private ResourceModel $resourceModel;

    public function __construct(ResourceModel $resourceModel) {
        $this->resourceModel = $resourceModel;
    }

    /**
     * @inheritdoc
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Exception
     */
    public function loadData(int $storeId, array $productIds): Traversable {
        if (empty($productIds)) {
            $productIds = $this->resourceModel->getAllProductIds($storeId);
        }

        // if any product is a child (variant) product - ensure to index only its parent, not the child product itself
        $productIds = $this->replaceProductVariantsWithTheirParents($productIds);

        // note: a simple product can only be a child of a single configurable product, but can be a child of multiple grouped or bundle products
        // TODO: verify what is published when a grouped product or its child is edited (expecting only parent with all children to be published)
        // TODO: verify what is published when a  bundle product or its child is edited (expecting only parent with all children to be published)

        // 1. Publish edited and added products
        $publishedProductIds = [];
        $lastProductId = 0;
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
     * @param int[] $productIds
     * @return int[]
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

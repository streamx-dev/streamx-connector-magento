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
            // TODO this scenario is not covered by any test
            $productIds = $this->resourceModel->getAllProductIds($storeId);
            if (empty($productIds)) {
                return []; // no products available for the store
            }
        }

        $productIds = array_map('intval', $productIds);

        $allParentsOfVariants = $this->resourceModel->retrieveParentsForVariants($productIds);
        $allVariantsOrParents = $this->resourceModel->retrieveVariantsForParents($productIds);
        $productIds = array_unique(array_merge($productIds, $allParentsOfVariants, $allVariantsOrParents));

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
}

<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use StreamX\ConnectorCatalog\Model\ResourceModel\Product\Bundle as Resource;
use StreamX\ConnectorCore\Api\DataProviderInterface;

class BundleOptionsData implements DataProviderInterface
{
    private Resource $resourceModel;

    public function __construct(Resource $resource)
    {
        $this->resourceModel = $resource;
    }

    /**
     * @inheritdoc
     */
    public function addData(array &$indexData, int $storeId): void
    {
        $this->resourceModel->clear();
        $this->resourceModel->setProducts($indexData);

        $productBundleOptions = $this->resourceModel->loadBundleOptions($storeId);

        foreach ($productBundleOptions as $productId => $bundleOptions) {
            $indexData[$productId]['bundle_options'] = [];

            foreach ($bundleOptions as $option) {
                $indexData[$productId]['bundle_options'][] = $option;
            }
        }

        $this->resourceModel->clear();
        $productBundleOptions = null;
    }
}

<?php

namespace Divante\VsbridgeIndexerCatalog\Model\Indexer\DataProvider\Product;

use Divante\VsbridgeIndexerCatalog\Model\ResourceModel\Product\Bundle as Resource;
use Divante\VsbridgeIndexerCore\Api\DataProviderInterface;

class BundleOptionsData implements DataProviderInterface
{
    /**
     * @var \Divante\VsbridgeIndexerCatalog\Model\ResourceModel\Product\Bundle
     */
    private $resourceModel;

    /**
     * BundleData constructor.
     *
     * @param Resource $resource
     */
    public function __construct(Resource $resource)
    {
        $this->resourceModel = $resource;
    }

    /**
     * @inheritdoc
     */
    public function addData(array $indexData, $storeId)
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

        return $indexData;
    }
}

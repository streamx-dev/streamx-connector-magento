<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use StreamX\ConnectorCore\Api\DataProviderInterface;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\Links as LinkResourceModel;

class ProductLinksData implements DataProviderInterface
{
    private LinkResourceModel $resourceModel;

    public function __construct(LinkResourceModel $resource)
    {
        $this->resourceModel = $resource;
    }

    /**
     * @inheritdoc
     */
    public function addData(array $indexData, int $storeId): array
    {
        $this->resourceModel->clear();
        $this->resourceModel->setProducts($indexData);

        foreach ($indexData as &$productDTO) {
            $productDTO['product_links'] = $this->resourceModel->getLinkedProduct($productDTO);
        }

        $this->resourceModel->clear();

        return $indexData;
    }
}

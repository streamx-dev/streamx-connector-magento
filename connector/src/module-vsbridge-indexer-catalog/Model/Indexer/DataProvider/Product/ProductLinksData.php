<?php

namespace Divante\VsbridgeIndexerCatalog\Model\Indexer\DataProvider\Product;

use Divante\VsbridgeIndexerCore\Api\DataProviderInterface;
use Divante\VsbridgeIndexerCatalog\Model\ResourceModel\Product\Links as LinkResourceModel;

class ProductLinksData implements DataProviderInterface
{

    /**
     * @var LinkResourceModel
     */
    private $resourceModel;

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

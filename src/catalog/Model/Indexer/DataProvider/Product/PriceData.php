<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use StreamX\ConnectorCore\Api\DataProviderInterface;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\Prices as Resource;

class PriceData extends DataProviderInterface
{
    private Resource $resourcePriceModel;

    public function __construct(Resource $resource)
    {
        $this->resourcePriceModel = $resource;
    }

    /**
     * @throws Exception
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function addData(array $indexData, int $storeId): array
    {
        $productIds = array_keys($indexData);
        $priceData = $this->resourcePriceModel->loadPriceData($storeId, $productIds);

        foreach ($priceData as $productId => $priceDataRow) {
            // TODO currently not required but may come back:
            // $indexData[$productId]['final_price'] = (float)$priceDataRow['final_price'];

            if (isset($priceDataRow['price'])) {
                // TODO currently not required but may come back:
                // $indexData[$productId]['regular_price'] = (float)$priceDataRow['price'];
            }
        }

        return $indexData;
    }

}

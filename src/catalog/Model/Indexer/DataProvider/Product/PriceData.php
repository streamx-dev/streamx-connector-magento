<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use StreamX\ConnectorCore\Api\DataProviderInterface;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\Prices as Resource;

class PriceData implements DataProviderInterface
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
    public function addData(array &$indexData, int $storeId): void
    {
        $productIds = array_keys($indexData);
        $priceData = $this->resourcePriceModel->loadPriceDataFromPriceIndex($storeId, $productIds);

        foreach ($priceData as $productId => $priceDataRow) {
            if (isset($priceDataRow['price'])) {
                // note: if price was already set from value of price attribute - it will be overwritten by indexed price here, which takes precedence
                $indexData[$productId]['price']['value'] = (float)$priceDataRow['price'];
            }

            if (isset($priceDataRow['final_price'])) {
                $indexData[$productId]['price']['discountedValue'] = (float)$priceDataRow['final_price'];
            }
        }
    }

}

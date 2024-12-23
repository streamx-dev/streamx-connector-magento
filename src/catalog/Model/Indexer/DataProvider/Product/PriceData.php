<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use StreamX\ConnectorCore\Api\DataProviderInterface;
use StreamX\ConnectorCatalog\Api\LoadTierPricesInterface;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\Prices as Resource;

class PriceData implements DataProviderInterface
{
    private Resource $resourcePriceModel;
    private LoadTierPricesInterface $tierPriceLoader;

    public function __construct(
        Resource $resource,
        LoadTierPricesInterface $loadTierPrices
    ) {
        $this->resourcePriceModel = $resource;
        $this->tierPriceLoader = $loadTierPrices;
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
            $indexData[$productId]['final_price'] = $this->preparePrice($priceDataRow['final_price']);

            if (isset($priceDataRow['price'])) {
                $indexData[$productId]['regular_price'] = $this->preparePrice($priceDataRow['price']);
            }
        }

        return $this->tierPriceLoader->execute($indexData, $storeId);
    }

    private function preparePrice(?string $value): float
    {
        return (float)$value;
    }
}

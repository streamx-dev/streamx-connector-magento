<?php declare(strict_types=1);

namespace Divante\VsbridgeIndexerCatalog\Model\Indexer\DataProvider\Product;

use Divante\VsbridgeIndexerCore\Api\DataProviderInterface;
use Divante\VsbridgeIndexerCatalog\Api\LoadTierPricesInterface;
use Divante\VsbridgeIndexerCatalog\Model\ResourceModel\Product\Prices as Resource;

class PriceData implements DataProviderInterface
{
    /**
     * @var \Divante\VsbridgeIndexerCatalog\Model\ResourceModel\Product\Prices
     */
    private $resourcePriceModel;

    /**
     * @var LoadTierPricesInterface
     */
    private $tierPriceLoader;

    public function __construct(
        Resource $resource,
        LoadTierPricesInterface $loadTierPrices
    ) {
        $this->resourcePriceModel = $resource;
        $this->tierPriceLoader = $loadTierPrices;
    }

    /**
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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

    /**
     * @param string $value
     *
     * @return float
     */
    private function preparePrice($value): float
    {
        return (float)$value;
    }
}

<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use StreamX\ConnectorCatalog\Model\SystemConfig\CatalogConfig;
use StreamX\ConnectorCore\Api\DataProviderInterface;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\IndexedPrices as Resource;

class IndexedPricesProvider implements DataProviderInterface
{
    private Resource $resourcePriceModel;
    private CatalogConfig $catalogConfig;

    public function __construct(Resource $resource, CatalogConfig $catalogConfig)
    {
        $this->resourcePriceModel = $resource;
        $this->catalogConfig = $catalogConfig;
    }

    /**
     * @throws Exception
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function addData(array &$indexData, int $storeId): void
    {
        if (!$this->catalogConfig->usePricesIndex()) {
            return;
        }

        $productIds = array_keys($indexData);
        $indexedPrices = $this->resourcePriceModel->loadIndexedPrices($storeId, $productIds);

        foreach ($indexedPrices as $productId => $indexedPrice) {
            if (isset($indexedPrice['price'])) {
                // note: if price was already set from value of price attribute - it will be overwritten by indexed price here, which takes precedence
                $indexData[$productId]['price']['value'] = (float)$indexedPrice['price'];
            }

            if (isset($indexedPrice['final_price'])) {
                $indexData[$productId]['price']['discountedValue'] = (float)$indexedPrice['final_price'];
            }
        }
    }

}

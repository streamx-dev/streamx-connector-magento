<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use StreamX\ConnectorCatalog\Api\LoadQuantityInterface;
use StreamX\ConnectorCore\Api\DataProviderInterface;

class Quantity implements DataProviderInterface
{
    private LoadQuantityInterface $loadQuantity;

    public function __construct(LoadQuantityInterface $loadQuantity)
    {
        $this->loadQuantity = $loadQuantity;
    }

    public function addData(array $indexData, int $storeId): array
    {
        $quantityData = $this->loadQuantity->execute($indexData, $storeId);

        foreach ($quantityData as $quantityDataRow) {
            $productId = (int) $quantityDataRow['product_id'];
            $quantity = $quantityDataRow['qty'];
            settype($quantity, 'float');
            $indexData[$productId]['qty'] = $quantity;
        }

        $quantityData = null;

        return $indexData;
    }
}

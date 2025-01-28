<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use StreamX\ConnectorCatalog\Model\LoadQuantity;
use StreamX\ConnectorCore\Api\DataProviderInterface;

class QuantityData implements DataProviderInterface
{
    private LoadQuantity $loadQuantity;

    public function __construct(LoadQuantity $loadQuantity)
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
            $indexData[$productId]['quantity'] = $quantity;
        }

        $quantityData = null;

        return $indexData;
    }
}

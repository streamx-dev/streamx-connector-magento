<?php

namespace StreamX\ConnectorCatalog\ArrayConverter\Product;

use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use StreamX\ConnectorCatalog\Api\ArrayConverter\Product\InventoryConverterInterface;

class InventoryConverter implements InventoryConverterInterface
{

    private StockConfigurationInterface $stockConfiguration;

    public function __construct(
        StockConfigurationInterface $stockConfiguration
    ) {
        $this->stockConfiguration = $stockConfiguration;
    }

    public function prepareInventoryData(int $storeId, array $inventory): array
    {
        if (!empty($inventory[StockItemInterface::USE_CONFIG_MIN_QTY])) {
            $inventory[StockItemInterface::MIN_QTY] = $this->stockConfiguration->getMinQty($storeId);
        }

        if (!empty($inventory[StockItemInterface::USE_CONFIG_MIN_SALE_QTY])) {
            $inventory[StockItemInterface::MIN_SALE_QTY] = $this->stockConfiguration->getMinSaleQty($storeId);
        }

        if (!empty($inventory[StockItemInterface::USE_CONFIG_MAX_SALE_QTY])) {
            $inventory[StockItemInterface::MAX_SALE_QTY] = $this->stockConfiguration->getMaxSaleQty($storeId);
        }

        if (!empty($inventory[StockItemInterface::USE_CONFIG_NOTIFY_STOCK_QTY])) {
            $inventory[StockItemInterface::NOTIFY_STOCK_QTY] = $this->stockConfiguration->getNotifyStockQty($storeId);
        }

        if (!empty($inventory[StockItemInterface::USE_CONFIG_QTY_INCREMENTS])) {
            $inventory[StockItemInterface::QTY_INCREMENTS] = $this->stockConfiguration->getQtyIncrements($storeId);
        }

        if (!empty($inventory[StockItemInterface::USE_CONFIG_ENABLE_QTY_INC])) {
            $inventory[StockItemInterface::ENABLE_QTY_INCREMENTS] = $this->stockConfiguration->getEnableQtyIncrements($storeId);
        }

        if (!empty($inventory[StockItemInterface::USE_CONFIG_BACKORDERS])) {
            $inventory[StockItemInterface::BACKORDERS] = $this->stockConfiguration->getBackorders($storeId);
        }

        return $inventory;
    }

}

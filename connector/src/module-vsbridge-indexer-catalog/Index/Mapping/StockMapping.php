<?php declare(strict_types=1);

namespace Divante\VsbridgeIndexerCatalog\Index\Mapping;

use Divante\VsbridgeIndexerCore\Api\Mapping\FieldInterface;

/**
 * Class StockMapping
 */
class StockMapping
{
    /**
     * @return array
     */
    public function get()
    {
        return [
            'product_id' => ['type' => FieldInterface::TYPE_LONG],
            'backorders' => ['type' => FieldInterface::TYPE_BOOLEAN],
            'enable_qty_increments' => ['type' => FieldInterface::TYPE_BOOLEAN],
            'is_decimal_divided' => ['type' => FieldInterface::TYPE_BOOLEAN],
            'is_in_stock' => ['type' => FieldInterface::TYPE_BOOLEAN],
            'is_qty_decimal' => ['type' => FieldInterface::TYPE_BOOLEAN],
            'item_id' => ['type' => FieldInterface::TYPE_LONG],
            'low_stock_date' => [
                'type' => FieldInterface::TYPE_DATE,
                'format' => FieldInterface::DATE_FORMAT,
            ],
            'manage_stock' => ['type' => FieldInterface::TYPE_BOOLEAN],
            'max_sale_qty' => ['type' => FieldInterface::TYPE_DOUBLE],
            'min_qty' => ['type' => FieldInterface::TYPE_DOUBLE],
            'min_sale_qty' => ['type' => FieldInterface::TYPE_DOUBLE],
            'notify_stock_qty' => ['type' => FieldInterface::TYPE_DOUBLE],
            'qty' => ['type' => FieldInterface::TYPE_DOUBLE],
            'qty_increments' => ['type' => FieldInterface::TYPE_DOUBLE],
            'stock_id' => ['type' => FieldInterface::TYPE_LONG],
            'stock_status' => ['type' => FieldInterface::TYPE_LONG] ,
            'stock_status_changed_auto' => ['type' => FieldInterface::TYPE_BOOLEAN],
            'use_config_backorders' => ['type' => FieldInterface::TYPE_BOOLEAN],
            'use_config_enable_qty_inc' => ['type' => FieldInterface::TYPE_BOOLEAN],
            'use_config_manage_stock' => ['type' => FieldInterface::TYPE_BOOLEAN],
            'use_config_max_sale_qty' => ['type' => FieldInterface::TYPE_BOOLEAN],
            'use_config_min_qty' => ['type' => FieldInterface::TYPE_BOOLEAN],
            'use_config_min_sale_qty' => ['type' => FieldInterface::TYPE_BOOLEAN],
            'use_config_notify_stock_qty' => ['type' => FieldInterface::TYPE_BOOLEAN],
            'use_config_qty_increments' => ['type' => FieldInterface::TYPE_BOOLEAN],
        ];
    }
}

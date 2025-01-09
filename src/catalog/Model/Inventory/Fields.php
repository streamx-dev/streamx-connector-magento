<?php


declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Inventory;

class Fields
{
    private array $childRequiredFields = [
        'product_id',
        'is_in_stock',
        'qty',
    ];

    private array $fields = [
        'product_id',
        'item_id',
        'stock_id',
        'qty',
        'is_in_stock',
        'is_qty_decimal',
        'use_config_min_qty',
        'use_config_min_sale_qty',
        'use_config_manage_stock',
        'manage_stock',
        'low_stock_date',
    ];

    public function getRequiredColumns(): array
    {
        return $this->fields;
    }

    public function getChildRequiredColumns(): array
    {
        return $this->childRequiredFields;
    }
}

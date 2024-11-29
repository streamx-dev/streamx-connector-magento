<?php

namespace Divante\VsbridgeIndexerCatalog\Model\Config\Source\Product;

use Magento\Catalog\Api\Data\ProductAttributeInterface;

class Attributes extends AbstractAttributeSource
{
    /**
     *
     */
    const GENERAL_RESTRICTED_ATTRIBUTES = [
        'sku',
        'url_path',
        'url_key',
        'name',
        'visibility',
        'status',
        'tier_price',
        'price',
        'price_type',
        'gallery',
        'status',
        'category_ids',
        'swatch_image',
        'quantity_and_stock_status',
        'options_container',
    ];

    /**
     * @inheritDoc
     */
    public function canAddAttribute(ProductAttributeInterface $attribute): bool
    {
        return !in_array($attribute->getAttributeCode(), self::GENERAL_RESTRICTED_ATTRIBUTES);
    }
}

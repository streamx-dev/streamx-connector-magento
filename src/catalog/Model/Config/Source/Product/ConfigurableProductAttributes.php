<?php

namespace StreamX\ConnectorCatalog\Model\Config\Source\Product;

use Magento\Catalog\Api\Data\ProductAttributeInterface;

class ConfigurableProductAttributes extends AbstractAttributeSource
{
    const ATTRIBUTES_NOT_ALLOWED_IN_SELECT_LIST = [
        // always loaded product attributes - don't allow the user to select them or not
        'name',
        'image',
        'description',
        'price',
        'url_key',
        'media_gallery',

        // explicitly not allowed attributes
        'tier_price',
        'gallery',
        'category_ids',
        'swatch_image',
        'quantity_and_stock_status',
        'options_container',
    ];

    /**
     * @inheritDoc
     */
    public function isAllowedInSelectList(ProductAttributeInterface $attribute): bool
    {
        return !in_array($attribute->getAttributeCode(), self::ATTRIBUTES_NOT_ALLOWED_IN_SELECT_LIST);
    }
}

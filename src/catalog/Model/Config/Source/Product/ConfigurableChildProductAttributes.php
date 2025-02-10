<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Config\Source\Product;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product as Resource;

class ConfigurableChildProductAttributes extends AbstractAttributeSource
{
    const ATTRIBUTES_NOT_ALLOWED_IN_SELECT_LIST = [
        // always loaded child product attributes - don't allow the user to select them or not
        'name',
        'image',
        'price',
        'url_key',
        'media_gallery',

        // explicitly not allowed attributes
        'gallery',
        'category_ids',
        'swatch_image',
        'quantity_and_stock_status',
        'options_container',
    ];

    private Resource $productResource;

    public function __construct(CollectionFactory $collectionFactory, Resource $productResource)
    {
        parent::__construct($collectionFactory);
        $this->productResource = $productResource;
    }

    /**
     * @inheritDoc
     */
    public function isAllowedInSelectList(ProductAttributeInterface $attribute): bool
    {
        if (in_array($attribute->getAttributeCode(), self::ATTRIBUTES_NOT_ALLOWED_IN_SELECT_LIST)) {
            return false;
        }

        if (in_array($attribute->getAttributeId(), $this->productResource->getConfigurableAttributeIds())) {
            return false;
        }

        return true;
    }
}

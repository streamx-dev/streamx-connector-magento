<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Config\Source\Product;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product as Resource;

class ChildProductAttributeSource extends BaseProductAttributeSource
{
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
        if (!parent::isAllowedInSelectList($attribute)) {
            return false;
        }

        if (in_array($attribute->getAttributeId(), $this->productResource->getConfigurableAttributeIds())) {
            return false;
        }

        return true;
    }
}

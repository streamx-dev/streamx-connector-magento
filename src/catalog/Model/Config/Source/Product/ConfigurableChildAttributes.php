<?php

namespace StreamX\ConnectorCatalog\Model\Config\Source\Product;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use StreamX\ConnectorCatalog\Model\Attributes\ChildProductAttributes;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product as Resource;

class ConfigurableChildAttributes extends AbstractAttributeSource
{
    private array $restrictedAttributes;
    private Resource $productResource;

    public function __construct(CollectionFactory $collectionFactory, Resource $productResource)
    {
        $this->productResource = $productResource;

        parent::__construct($collectionFactory);

        $this->restrictedAttributes = array_merge(
            Attributes::GENERAL_RESTRICTED_ATTRIBUTES,
            ChildProductAttributes::MINIMAL_ATTRIBUTE_SET
        );
    }

    /**
     * @inheritDoc
     */
    public function canAddAttribute(ProductAttributeInterface $attribute): bool
    {
        if (in_array($attribute->getAttributeCode(), $this->restrictedAttributes)) {
            return false;
        }

        if (in_array($attribute->getAttributeId(), $this->productResource->getConfigurableAttributeIds())) {
            return false;
        }

        return true;
    }
}

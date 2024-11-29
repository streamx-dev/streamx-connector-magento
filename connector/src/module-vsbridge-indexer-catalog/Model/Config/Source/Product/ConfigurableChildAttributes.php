<?php

namespace Divante\VsbridgeIndexerCatalog\Model\Config\Source\Product;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Divante\VsbridgeIndexerCatalog\Model\Attributes\ConfigurableAttributes;
use Divante\VsbridgeIndexerCatalog\Model\ResourceModel\Product as Resource;

class ConfigurableChildAttributes extends AbstractAttributeSource
{

    /**
     * @var
     */
    private $restrictedAttributes;

    /**
     * @var Resource
     */
    private $productResource;

    /**
     * Attributes constructor.
     *
     * @param CollectionFactory $collectionFactory
     * @param Resource $productResource
     */
    public function __construct(CollectionFactory $collectionFactory, Resource $productResource)
    {
        $this->productResource = $productResource;

        parent::__construct($collectionFactory);
    }

    /**
     * @inheritDoc
     *
     * @param \Magento\Catalog\Api\Data\ProductAttributeInterface $attribute
     *
     * @return bool
     */
    public function canAddAttribute(\Magento\Catalog\Api\Data\ProductAttributeInterface $attribute): bool
    {
        if (in_array($attribute->getAttributeCode(), $this->getRestrictedAttributes())) {
            return false;
        }

        if (in_array($attribute->getAttributeId(), $this->productResource->getConfigurableAttributeIds())) {
            return false;
        }

        return true;
    }

    /**
     * Retrieve restricted attributes list
     *
     * @return array
     */
    private function getRestrictedAttributes()
    {
        if (null === $this->restrictedAttributes) {
            $this->restrictedAttributes = array_merge(
                Attributes::GENERAL_RESTRICTED_ATTRIBUTES,
                ConfigurableAttributes::MINIMAL_ATTRIBUTE_SET
            );
        }

        return $this->restrictedAttributes;
    }
}

<?php

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Category;

use Magento\Catalog\Model\ResourceModel\Category\Attribute\Collection;
use Magento\Catalog\Model\ResourceModel\Category\Attribute\CollectionFactory;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\Exception\LocalizedException;

class LoadAttributes
{
    private CollectionFactory $attributeCollectionFactory;

    /**
     * Mapping attributes by code
     */
    private array $attributesByCode = [];

    public function __construct(CollectionFactory $attributeCollectionFactory)
    {
        $this->attributeCollectionFactory = $attributeCollectionFactory;
    }

    private function initAttributes(): void
    {
        if (empty($this->attributesByCode)) {
            $attributeCollection = $this->getAttributeCollection();

            foreach ($attributeCollection as $attribute) {
                $this->attributesByCode[$attribute->getAttributeCode()] = $attribute;
            }
        }
    }

    /**
     * @throws LocalizedException
     */
    public function getAttributeByCode(string $attributeCode): Attribute
    {
        $this->initAttributes();

        if (isset($this->attributesByCode[$attributeCode])) {
            return $this->attributesByCode[$attributeCode];
        }

        throw new LocalizedException(__("Attribute $attributeCode not found."));
    }

    private function getAttributeCollection(): Collection
    {
        return $this->attributeCollectionFactory->create();
    }
}

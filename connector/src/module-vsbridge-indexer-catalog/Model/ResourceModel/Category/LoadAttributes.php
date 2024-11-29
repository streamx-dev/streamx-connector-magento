<?php

namespace Divante\VsbridgeIndexerCatalog\Model\ResourceModel\Category;

use Magento\Catalog\Model\ResourceModel\Category\Attribute\Collection;
use Magento\Catalog\Model\ResourceModel\Category\Attribute\CollectionFactory;
use Magento\Eav\Model\Entity\Attribute;

class LoadAttributes
{
    /**
     * @var CollectionFactory
     */
    private $attributeCollectionFactory;

    /**
     * Product attributes by id
     *
     * @var array
     */
    private $attributesById;

    /**
     * Mapping attribute code to id
     * @var array
     */
    private $attributeCodeToId = [];

    public function __construct(CollectionFactory $attributeCollectionFactory)
    {
        $this->attributeCollectionFactory = $attributeCollectionFactory;
    }

    /**
     * @return Attribute[]
     */
    public function execute()
    {
        return $this->initAttributes();
    }

    /**
     * @return Attribute[]
     */
    private function initAttributes()
    {
        if (null === $this->attributesById) {
            $this->attributesById = [];
            $attributeCollection = $this->getAttributeCollection();

            foreach ($attributeCollection as $attribute) {
                $this->attributesById[$attribute->getId()] = $attribute;
                $this->attributeCodeToId[$attribute->getAttributeCode()] = $attribute->getId();
            }
        }

        return $this->attributesById;
    }

    /**
     * @return Attribute
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAttributeById(int $attributeId)
    {
        $this->initAttributes();

        if (isset($this->attributesById[$attributeId])) {
            return $this->attributesById[$attributeId];
        }

        throw new \Magento\Framework\Exception\LocalizedException(__('Attribute not found.'));
    }

    /**
     * @return Attribute
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAttributeByCode(string $attributeCode)
    {
        $this->initAttributes();

        if (isset($this->attributeCodeToId[$attributeCode])) {
            $attributeId = $this->attributeCodeToId[$attributeCode];

            return $this->attributesById[$attributeId];
        }

        throw new \Magento\Framework\Exception\LocalizedException(__('Attribute not found.'));
    }

    /**
     * @return Collection
     */
    private function getAttributeCollection()
    {
        return $this->attributeCollectionFactory->create();
    }
}

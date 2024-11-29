<?php

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Product;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Framework\Serialize\Serializer\Json;

class LoadAttributes
{
    /**
     * @var AttributeCollectionFactory
     */
    private $attributeCollectionFactory;

    /**
     * @var Json
     */
    private $serializer;

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

    public function __construct(
        Json $serializer,
        AttributeCollectionFactory $attributeCollectionFactory
    ) {
        $this->serializer = $serializer;
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
            $attributeCollection = $this->getAttributeCollection();

            foreach ($attributeCollection as $attribute) {
                $this->addAttribute($attribute);
            }
        }

        return $this->attributesById;
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAttributeById(int $attributeId): Attribute
    {
        $this->initAttributes();

        if (isset($this->attributesById[$attributeId])) {
            return $this->attributesById[$attributeId];
        }

        throw new \Magento\Framework\Exception\LocalizedException(__('Attribute not found.'));
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAttributeByCode(string $attributeCode): Attribute
    {
        $this->initAttributes();
        $this->loadAttributeByCode($attributeCode);

        if (isset($this->attributeCodeToId[$attributeCode])) {
            $attributeId = $this->attributeCodeToId[$attributeCode];

            return $this->attributesById[$attributeId];
        }

        throw new \Magento\Framework\Exception\LocalizedException(__('Attribute not found.'));
    }

    private function loadAttributeByCode(string $attributeCode)
    {
        if (!isset($this->attributeCodeToId[$attributeCode])) {
            $attributeCollection = $this->getAttributeCollection();
            $attributeCollection->addFieldToFilter('attribute_code', $attributeCode);
            $attributeCollection->setPageSize(1)->setCurPage(1);

            $attribute = $attributeCollection->getFirstItem();

            if ($attribute->getId()) {
                $this->addAttribute($attribute);
            }
        }
    }

    private function addAttribute(Attribute $attribute)
    {
        $this->prepareAttribute($attribute);
        $this->attributesById[$attribute->getId()] = $attribute;
        $this->attributeCodeToId[$attribute->getAttributeCode()] = $attribute->getId();
    }

    private function prepareAttribute(Attribute $attribute): Attribute
    {
        $additionalData = (string)$attribute->getData('additional_data');

        if (!empty($additionalData)) {
            $additionalData = $this->serializer->unserialize($additionalData);

            if (isset($additionalData['swatch_input_type'])) {
                $attribute->setData('swatch_input_type', $additionalData['swatch_input_type']);
            }
        }

        return $attribute;
    }

    private function getAttributeCollection(): Collection
    {
        return $this->attributeCollectionFactory->create();
    }
}

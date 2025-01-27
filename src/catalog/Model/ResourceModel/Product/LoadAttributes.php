<?php

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Product;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;

class LoadAttributes
{
    private AttributeCollectionFactory $attributeCollectionFactory;
    private Json $serializer;

    /**
     * Product attributes by id
     */
    private array $attributesById = [];
    private array $attributeCodeToId = [];

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
    public function getAttributes(): array
    {
        if (empty($this->attributesById)) {
            $this->initAttributes();
        }

        return $this->attributesById;
    }

    private function initAttributes(): void
    {
        $attributeCollection = $this->getAttributeCollection();

        foreach ($attributeCollection as $attribute) {
            $this->setSwatchInputType($attribute);
            $this->attributesById[$attribute->getId()] = $attribute;
            $this->attributeCodeToId[$attribute->getAttributeCode()] = $attribute->getId();
        }
    }

    /**
     * @throws LocalizedException
     */
    public function getAttributeById(int $attributeId): Attribute
    {
        $this->getAttributes();

        if (isset($this->attributesById[$attributeId])) {
            return $this->attributesById[$attributeId];
        }

        throw new LocalizedException(__('Attribute not found.'));
    }

    /**
     * @throws LocalizedException
     */
    public function getAttributeByCode(string $attributeCode): Attribute
    {
        $this->getAttributes();
        $this->loadAttributeByCode($attributeCode);

        if (isset($this->attributeCodeToId[$attributeCode])) {
            $attributeId = $this->attributeCodeToId[$attributeCode];

            return $this->attributesById[$attributeId];
        }

        throw new LocalizedException(__('Attribute not found.'));
    }

    private function loadAttributeByCode(string $attributeCode): void
    {
        if (!isset($this->attributeCodeToId[$attributeCode])) {
            $attributeCollection = $this->getAttributeCollection();
            $attributeCollection->addFieldToFilter('attribute_code', $attributeCode);
            $attributeCollection->setPageSize(1)->setCurPage(1);

            $attribute = $attributeCollection->getFirstItem();

            if ($attribute->getId()) {
                $this->setSwatchInputType($attribute);
                $this->attributesById[$attribute->getId()] = $attribute;
                $this->attributeCodeToId[$attribute->getAttributeCode()] = $attribute->getId();
            }
        }
    }

    private function setSwatchInputType(Attribute $attribute): Attribute
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

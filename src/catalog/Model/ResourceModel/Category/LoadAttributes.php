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
     * Product attributes by id
     */
    private ?array $attributesById = null;

    /**
     * Mapping attribute code to id
     */
    private array $attributeCodeToId = [];

    public function __construct(CollectionFactory $attributeCollectionFactory)
    {
        $this->attributeCollectionFactory = $attributeCollectionFactory;
    }

    /**
     * @return Attribute[]
     */
    public function execute(): array
    {
        return $this->initAttributes();
    }

    /**
     * @return Attribute[]
     */
    private function initAttributes(): array
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
     * @throws LocalizedException
     */
    public function getAttributeById(int $attributeId): Attribute
    {
        $this->initAttributes();

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
        $this->initAttributes();

        if (isset($this->attributeCodeToId[$attributeCode])) {
            $attributeId = $this->attributeCodeToId[$attributeCode];

            return $this->attributesById[$attributeId];
        }

        throw new LocalizedException(__('Attribute not found.'));
    }

    private function getAttributeCollection(): Collection
    {
        return $this->attributeCollectionFactory->create();
    }
}

<?php

declare(strict_types = 1);

namespace Divante\VsbridgeIndexerCatalog\Model\Attribute;

use Divante\VsbridgeIndexerCatalog\Model\ResourceModel\Product\AttributeDataProvider;

use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection as OptionCollection;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Eav\Model\Entity\Attribute\Source\Table as SourceTable;
use Magento\Swatches\Model\Swatch;

class LoadOptions
{
    /**
     * @var AttributeDataProvider
     */
    private $attributeDataProvider;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var OptionCollectionToArray
     */
    private $optionCollectionToArray;

    /**
     * @var array
     */
    private $optionsByAttribute = [];

    public function __construct(
        CollectionFactory $collectionFactory,
        OptionCollectionToArray $optionCollectionToArray,
        AttributeDataProvider $attributeDataProvider
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->attributeDataProvider = $attributeDataProvider;
        $this->optionCollectionToArray = $optionCollectionToArray;
    }

    /**
     * @return string
     */
    public function execute(string $attributeCode, int $storeId): array
    {
        $attributeModel = $this->attributeDataProvider->getAttributeByCode($attributeCode);
        $attributeModel->setStoreId($storeId);

        return $this->loadOptions($attributeModel);
    }

    /**
     * @return array
     */
    private function loadOptions(Attribute $attribute): array
    {
        $key = $attribute->getId() . '_' . $attribute->getStoreId();

        if (!isset($this->optionsByAttribute[$key])) {
            if ($this->useSourceModel($attribute)) {
                $source = $attribute->getSource();
                $options = $source->getAllOptions();
            } else {
                $loadSwatches = $this->isVisualSwatch($attribute);
                $optionCollection = $this->getOptionCollection($attribute);
                $additionalFields = [];

                if ($loadSwatches) {
                    $additionalFields['swatch'] = 'swatch';
                }

                $options = $this->toOptionArray($optionCollection, $additionalFields);
            }

            $this->optionsByAttribute[$key] = $options;
        }

        return $this->optionsByAttribute[$key];
    }

    /**
     * @return bool
     */
    private function useSourceModel(Attribute $attribute)
    {
        $source = $attribute->getSource();

        if ($source instanceof AbstractSource && !($source instanceof SourceTable)) {
            return true;
        }

        return false;
    }

    /**
     * @return OptionCollection
     */
    private function getOptionCollection(Attribute $attribute)
    {
        $loadSwatches = $this->isVisualSwatch($attribute);
        $attributeId = $attribute->getAttributeId();
        $storeId = $attribute->getStoreId();

        /** @var OptionCollection $options */
        $options = $this->collectionFactory->create();
        $options->setOrder('sort_order', 'asc');
        $options->setAttributeFilter($attributeId)
            ->setStoreFilter($storeId);

        if ($loadSwatches) {
            $options->getSelect()->joinLeft(
                ['swatch_table' => $options->getTable('eav_attribute_option_swatch')],
                'swatch_table.option_id = main_table.option_id AND swatch_table.store_id = 0',
                [
                    'swatch_value' => 'value',
                    'swatch_type' => 'type',
                ]
            );
        }

        return $options;
    }

    /**
     * @return bool
     */
    private function isVisualSwatch(Attribute $attribute)
    {
        return $attribute->getData('swatch_input_type') === Swatch::SWATCH_INPUT_TYPE_VISUAL
            || $attribute->getData('swatch_input_type') === Swatch::SWATCH_INPUT_TYPE_TEXT;
    }

    /**
     * @return array
     */
    private function toOptionArray(OptionCollection $collection, array $additional): array
    {
        return $this->optionCollectionToArray->execute($collection, $additional);
    }
}

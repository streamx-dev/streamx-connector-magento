<?php declare(strict_types = 1);

namespace StreamX\ConnectorCatalog\Model\Attribute;

use StreamX\ConnectorCatalog\Model\Attributes\AttributeOptionDefinition;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\LoadAttributes;

use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection as OptionCollection;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Eav\Model\Entity\Attribute\Source\Table as SourceTable;
use Magento\Swatches\Model\Swatch;

class LoadOptions
{
    private LoadAttributes $loadAttributes;
    private CollectionFactory $collectionFactory;
    private array $optionsByAttribute = [];

    public function __construct(
        CollectionFactory $collectionFactory,
        LoadAttributes $loadAttributes
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->loadAttributes = $loadAttributes;
    }

    /**
     * @return AttributeOptionDefinition[]
     */
    public function getOptions(string $attributeCode, int $storeId): array
    {
        $attribute = $this->loadAttributes->getAttributeByCode($attributeCode);
        $key = $attribute->getId() . '_' . $storeId;

        if (!isset($this->optionsByAttribute[$key])) {
            $this->optionsByAttribute[$key] = $this->loadOptions($attribute, $storeId);
        }

        return $this->optionsByAttribute[$key];
    }

    /**
     * @return AttributeOptionDefinition[]
     */
    private function loadOptions(\Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute, int $storeId): array
    {
        if ($this->useSourceModel($attribute)) {
            $source = $attribute->getSource();
            return array_map(fn($option) => new AttributeOptionDefinition(
                (int)$option['value'],
                (string)$option['label'],
                null
            ), $source->getAllOptions());
        } else {
            $loadSwatches = $this->isVisualSwatch($attribute);
            $optionCollection = $this->getOptionCollection($attribute, $storeId, $loadSwatches);
            return AttributeOptionDefinitionParser::parseToArray($optionCollection, $loadSwatches);
        }
    }

    private function useSourceModel(Attribute $attribute): bool
    {
        $source = $attribute->getSource();

        if ($source instanceof AbstractSource && !($source instanceof SourceTable)) {
            return true;
        }

        return false;
    }

    private function getOptionCollection(Attribute $attribute, int $storeId, bool $loadSwatches): OptionCollection
    {
        $attributeId = $attribute->getAttributeId();

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

    private function isVisualSwatch(Attribute $attribute): bool
    {
        return $attribute->getData('swatch_input_type') === Swatch::SWATCH_INPUT_TYPE_VISUAL
            || $attribute->getData('swatch_input_type') === Swatch::SWATCH_INPUT_TYPE_TEXT;
    }

}

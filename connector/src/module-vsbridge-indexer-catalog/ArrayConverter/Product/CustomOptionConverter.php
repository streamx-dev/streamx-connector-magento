<?php declare(strict_types = 1);

namespace Divante\VsbridgeIndexerCatalog\ArrayConverter\Product;

use Divante\VsbridgeIndexerCore\Indexer\DataFilter;
use Divante\VsbridgeIndexerCatalog\Api\ArrayConverter\Product\CustomOptionConverterInterface;

class CustomOptionConverter implements CustomOptionConverterInterface
{
    /**
     * @var array
     */
    private $fieldsToDelete = [
        'default_title',
        'store_title',
        'default_price',
        'default_price_type',
        'store_price',
        'store_price_type',
        'product_id',
    ];

    /**
     * @var DataFilter
     */
    private $dataFilter;

    public function __construct(DataFilter $dataFilter)
    {
        $this->dataFilter = $dataFilter;
    }

    public function process(array $options, array $optionValues): array
    {
        $groupOption = [];

        foreach ($optionValues as $optionValue) {
            $optionId = $optionValue['option_id'];
            $optionValue = $this->prepareValue($optionValue);
            $options[$optionId]['values'][] = $optionValue;
        }

        foreach ($options as $option) {
            $productId = $option['product_id'];
            $option = $this->prepareOption($option);
            $groupOption[$productId][] = $option;
        }

        return $groupOption;
    }

    private function prepareValue(array $option): array
    {
        $option = $this->unsetFields($option);
        unset($option['option_id']);

        return $option;
    }

    private function unsetFields(array $option): array
    {
        $option = $this->dataFilter->execute($option, $this->fieldsToDelete);

        if (isset($option['sku']) !== true) {
            unset($option['sku']);
        }

        if (isset($option['file_extension']) !== true) {
            unset($option['file_extension']);
        }

        return $option;
    }

    private function prepareOption(array $option): array
    {
        $option = $this->unsetFields($option);

        $option = $this->dataFilter->execute($option, $this->fieldsToDelete);

        if ('drop_down' === $option['type']) {
            $option['type'] = 'select';
        }

        return $option;
    }
}

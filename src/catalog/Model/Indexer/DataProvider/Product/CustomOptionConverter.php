<?php declare(strict_types = 1);

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

class CustomOptionConverter
{
    private array $fieldsToDelete = [
        'default_title',
        'store_title',
        'default_price',
        'default_price_type',
        'store_price',
        'store_price_type',
        'product_id',
        'sku',
        'file_extension'
    ];

    public function process(array $options, array $optionValues): array
    {
        $groupOption = [];

        foreach ($optionValues as $optionValue) {
            $optionId = $optionValue['option_id'];
            $this->removeFields($optionValue);
            unset($optionValue['option_id']);
            $options[$optionId]['values'][] = $optionValue;
        }

        foreach ($options as $option) {
            $productId = $option['product_id'];
            $this->removeFields($option);

            if ('drop_down' === $option['type']) {
                $option['type'] = 'select';
            }
            $groupOption[$productId][] = $option;
        }

        return $groupOption;
    }

    private function removeFields(array &$array): void
    {
        foreach ($this->fieldsToDelete as $key) {
            unset($array[$key]);
        }
    }

}

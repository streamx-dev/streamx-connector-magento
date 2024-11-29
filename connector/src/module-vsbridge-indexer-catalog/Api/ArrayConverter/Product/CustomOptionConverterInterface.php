<?php

namespace Divante\VsbridgeIndexerCatalog\Api\ArrayConverter\Product;

interface CustomOptionConverterInterface
{
    /**
     * @param array $options
     * @param array $optionValues
     *
     * @return array
     */
    public function process(array $options, array $optionValues): array;
}

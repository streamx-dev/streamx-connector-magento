<?php

namespace StreamX\ConnectorCatalog\Api\ArrayConverter\Product;

interface CustomOptionConverterInterface
{
    public function process(array $options, array $optionValues): array;
}

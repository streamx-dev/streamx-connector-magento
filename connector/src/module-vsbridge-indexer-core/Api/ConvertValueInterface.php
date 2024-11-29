<?php

namespace Divante\VsbridgeIndexerCore\Api;

interface ConvertValueInterface
{
    /**
     * @param string|array $value
     *
     * @return string|array|int|float
     */
    public function execute(MappingInterface $mapping, string $field, $value);
}

<?php

namespace Divante\VsbridgeIndexerCore\Api;

interface ConvertValueInterface
{
    /**
     * @param MappingInterface $mapping
     * @param string $field
     * @param string|array $value
     *
     * @return string|array|int|float
     */
    public function execute(MappingInterface $mapping, string $field, $value);
}

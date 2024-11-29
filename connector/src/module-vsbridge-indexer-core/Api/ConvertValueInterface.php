<?php

namespace Divante\VsbridgeIndexerCore\Api;

/**
 * Interface ConvertValueInterface
 */
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

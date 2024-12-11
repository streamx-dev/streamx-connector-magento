<?php

namespace StreamX\ConnectorCore\Api;

interface ConvertValueInterface
{
    /**
     * @param string|array $value
     *
     * @return string|array|int|float
     */
    public function execute(MappingInterface $mapping, string $field, $value);
}

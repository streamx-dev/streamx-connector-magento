<?php

namespace StreamX\ConnectorCore\Api\Index;

use StreamX\ConnectorCore\Api\DataProviderInterface;
use StreamX\ConnectorCore\Api\MappingInterface;

interface TypeInterface
{
    public function getName(): string;

    public function getMapping(): MappingInterface;

    /**
     * @return DataProviderInterface[]
     */
    public function getDataProviders(): array;

    public function getDataProvider(string $name): DataProviderInterface;
}

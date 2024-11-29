<?php

namespace Divante\VsbridgeIndexerCore\Api\Index;

interface TypeInterface
{
    public function getName(): string;

    /**
     * @return \Divante\VsbridgeIndexerCore\Api\MappingInterface
     */
    public function getMapping();

    /**
     * @return \Divante\VsbridgeIndexerCore\Api\DataProviderInterface[]
     */
    public function getDataProviders();

    /**
     * @return \Divante\VsbridgeIndexerCore\Api\DataProviderInterface
     */
    public function getDataProvider(string $name);
}

<?php

namespace Divante\VsbridgeIndexerCore\Api\Index;

/**
 * Interface TypeInterface
 */
interface TypeInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return \Divante\VsbridgeIndexerCore\Api\MappingInterface
     */
    public function getMapping();

    /**
     * @return \Divante\VsbridgeIndexerCore\Api\DataProviderInterface[]
     */
    public function getDataProviders();

    /**
     * @param string $name
     * @return \Divante\VsbridgeIndexerCore\Api\DataProviderInterface
     */
    public function getDataProvider(string $name);
}

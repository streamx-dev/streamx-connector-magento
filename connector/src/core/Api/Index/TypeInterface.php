<?php

namespace StreamX\ConnectorCore\Api\Index;

interface TypeInterface
{
    public function getName(): string;

    /**
     * @return \StreamX\ConnectorCore\Api\MappingInterface
     */
    public function getMapping();

    /**
     * @return \StreamX\ConnectorCore\Api\DataProviderInterface[]
     */
    public function getDataProviders();

    /**
     * @return \StreamX\ConnectorCore\Api\DataProviderInterface
     */
    public function getDataProvider(string $name);
}

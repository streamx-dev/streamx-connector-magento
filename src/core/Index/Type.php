<?php

namespace StreamX\ConnectorCore\Index;

use Exception;
use StreamX\ConnectorCore\Api\DataProviderInterface;
use StreamX\ConnectorCore\Api\Index\TypeInterface;
use StreamX\ConnectorCore\Api\MappingInterface;

class Type implements TypeInterface
{
    private string $name;
    private MappingInterface $mapping;
    private array $dataProviders;

    public function __construct(string $name, MappingInterface $mapping = null, array $dataProviders)
    {
        $this->name = $name;
        $this->mapping = $mapping;
        $this->dataProviders = $dataProviders;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMapping(): MappingInterface
    {
        return $this->mapping;
    }

    /**
     * @inheritdoc
     */
    public function getDataProviders(): array
    {
        return $this->dataProviders;
    }

    public function getDataProvider(string $name): DataProviderInterface
    {
        if (!isset($this->dataProviders[$name])) {
            throw new Exception("DataProvider $name does not exists.");
        }

        return $this->dataProviders[$name];
    }
}

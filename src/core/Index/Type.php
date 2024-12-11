<?php

namespace StreamX\ConnectorCore\Index;

use StreamX\ConnectorCore\Api\Index\TypeInterface;
use StreamX\ConnectorCore\Api\MappingInterface;

class Type implements TypeInterface
{
    /**
     * Type name.
     *
     * @var string
     */
    private $name;

    /**
     * Type mapping.
     *
     * @var
     */
    private $mapping;

    /**
     * Type dataProviders.
     *
     * @var
     */
    private $dataProviders;

    public function __construct($name, MappingInterface $mapping = null, array $dataProviders)
    {
        $this->name = $name;
        $this->mapping = $mapping;
        $this->dataProviders = $dataProviders;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function getMapping()
    {
        return $this->mapping;
    }

    /**
     * @inheritdoc
     */
    public function getDataProviders()
    {
        return $this->dataProviders;
    }

    /**
     * @inheritdoc
     */
    public function getDataProvider(string $name)
    {
        if (!isset($this->dataProviders[$name])) {
            throw new \Exception("DataProvider $name does not exists.");
        }

        return $this->dataProviders[$name];
    }
}

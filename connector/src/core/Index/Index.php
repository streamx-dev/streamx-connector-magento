<?php

namespace StreamX\ConnectorCore\Index;

use StreamX\ConnectorCore\Api\IndexInterface;
use StreamX\ConnectorCore\Api\Index\TypeInterface;

class Index implements IndexInterface
{

    /**
     * Name of the index.
     */
    private string $name;

    /**
     * Index types.
     *
     * @var TypeInterface[]
     */
    private $types;

    public function __construct(
        string $name,
        array $types
    ) {
        $this->name = $name;
        $this->types = $this->prepareTypes($types);
    }

    /**
     * @param TypeInterface[] $types
     *
     * @return TypeInterface[]
     */
    private function prepareTypes($types)
    {
        $preparedTypes = [];

        foreach ($types as $type) {
            $preparedTypes[$type->getName()] = $type;
        }

        return $preparedTypes;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function getType(string $typeName)
    {
        if (!isset($this->types[$typeName])) {
            throw new \InvalidArgumentException("Type $typeName is not available in index.");
        }

        return $this->types[$typeName];
    }
}

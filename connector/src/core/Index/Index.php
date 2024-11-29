<?php

namespace StreamX\ConnectorCore\Index;

use StreamX\ConnectorCore\Api\IndexInterface;
use StreamX\ConnectorCore\Api\Index\TypeInterface;

class Index implements IndexInterface
{

    /**
     * Name of the index.
     *
     * @var string
     */
    private $name;

    /**
     * Index types.
     *
     * @var TypeInterface[]
     */
    private $types;

    /**
     * @var string
     */
    private $alias;

    public function __construct(
        string $name,
        string $alias,
        array $types
    ) {
        $this->alias = $alias;
        $this->name = $name;
        $this->types = $this->prepareTypes($types);
    }

    public function isNew(): bool
    {
        return $this->alias !== $this->name;
    }

    public function getAlias(): string
    {
        return $this->alias;
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
    public function getTypes()
    {
        return $this->types;
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

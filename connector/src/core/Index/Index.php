<?php

namespace StreamX\ConnectorCore\Index;

use StreamX\ConnectorCore\Api\Index\TypeInterface;

class Index
{
    private string $name;

    /**
     * @var TypeInterface[]
     */
    private array $types = [];

    public function __construct(string $name, array $types) {
        $this->name = $name;

        foreach ($types as $type) {
            $this->types[$type->getName()] = $type;
        }
    }

    public function getName(): string {
        return $this->name;
    }

    /**
     * @throws \InvalidArgumentException When the type does not exists.
     */
    public function getType(string $typeName): TypeInterface {
        if (!isset($this->types[$typeName])) {
            throw new \InvalidArgumentException("Type $typeName is not available in index.");
        }

        return $this->types[$typeName];
    }
}

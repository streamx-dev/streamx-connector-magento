<?php

namespace Divante\VsbridgeIndexerCore\Api;

interface IndexInterface
{
    public function getName(): string;

    public function getAlias(): string;

    public function isNew(): bool;

    /**
     * @return \Divante\VsbridgeIndexerCore\Api\Index\TypeInterface[]
     */
    public function getTypes();

    /**
     * @return \Divante\VsbridgeIndexerCore\Api\Index\TypeInterface
     * @throws \InvalidArgumentException When the type does not exists.
     */
    public function getType(string $typeName);
}

<?php

namespace StreamX\ConnectorCore\Api;

interface IndexInterface
{
    public function getName(): string;

    public function getAlias(): string;

    public function isNew(): bool;

    /**
     * @return \StreamX\ConnectorCore\Api\Index\TypeInterface[]
     */
    public function getTypes();

    /**
     * @return \StreamX\ConnectorCore\Api\Index\TypeInterface
     * @throws \InvalidArgumentException When the type does not exists.
     */
    public function getType(string $typeName);
}

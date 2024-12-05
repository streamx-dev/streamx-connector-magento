<?php

namespace StreamX\ConnectorCore\Api;

interface IndexInterface
{
    public function getName(): string;

    /**
     * @return \StreamX\ConnectorCore\Api\Index\TypeInterface
     * @throws \InvalidArgumentException When the type does not exists.
     */
    public function getType(string $typeName);
}

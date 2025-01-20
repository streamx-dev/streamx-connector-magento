<?php

namespace StreamX\ConnectorCore\Api;

use StreamX\ConnectorCore\Api\Index\TypeInterface;

interface IndexersConfigInterface
{
    public function getByName(string $typeName): TypeInterface;
}
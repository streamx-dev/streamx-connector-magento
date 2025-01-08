<?php

namespace StreamX\ConnectorCatalog\Index\Mapping;

use StreamX\ConnectorCore\Api\MappingInterface;

class NoOpMapping implements MappingInterface
{
    public function getMappingProperties(): array
    {
        return ['properties' => []];
    }
}
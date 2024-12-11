<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Index\Mapping;

interface FieldMappingInterface
{
    /**
     * Retrieve field mapping options
     */
    public function get(): array;
}

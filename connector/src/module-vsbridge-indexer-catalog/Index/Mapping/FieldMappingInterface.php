<?php declare(strict_types=1);

namespace Divante\VsbridgeIndexerCatalog\Index\Mapping;

interface FieldMappingInterface
{
    /**
     * Retrieve field mapping options
     *
     * @return array
     */
    public function get(): array;
}

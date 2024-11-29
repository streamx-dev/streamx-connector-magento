<?php declare(strict_types=1);

namespace Divante\VsbridgeIndexerCatalog\Index\Mapping;

/**
 * Interface FieldMappingInterface
 */
interface FieldMappingInterface
{
    /**
     * Retrieve field mapping options
     *
     * @return array
     */
    public function get(): array;
}

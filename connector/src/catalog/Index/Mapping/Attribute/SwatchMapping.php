<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Index\Mapping\Attribute;

use StreamX\ConnectorCatalog\Index\Mapping\FieldMappingInterface;
use StreamX\ConnectorCore\Api\Mapping\FieldInterface;

class SwatchMapping implements FieldMappingInterface
{
    /**
     * Retrieve Swatch Mapping
     */
    public function get(): array
    {
        return [
            'properties' => [
                'value' => ['type' => FieldInterface::TYPE_TEXT],
                'type' => ['type' => FieldInterface::TYPE_SHORT], // to make it compatible with other fields
            ]
        ];
    }
}

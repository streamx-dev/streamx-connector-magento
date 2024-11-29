<?php declare(strict_types=1);

namespace Divante\VsbridgeIndexerCatalog\Index\Mapping\Attribute;

use Divante\VsbridgeIndexerCatalog\Index\Mapping\FieldMappingInterface;
use Divante\VsbridgeIndexerCore\Api\Mapping\FieldInterface;

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

<?php

declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Index\Mapping\Attribute;

use StreamX\ConnectorCatalog\Index\Mapping\FieldMappingInterface;
use StreamX\ConnectorCore\Api\Mapping\FieldInterface;

class OptionMapping implements FieldMappingInterface
{
    private SwatchMapping $swatchMapping;

    public function __construct(SwatchMapping $generalMapping)
    {
        $this->swatchMapping = $generalMapping;
    }

    public function get(): array
    {
        return [
            'properties' => [
                'value' => ['type' => FieldInterface::TYPE_TEXT],
                'label' => ['type' => FieldInterface::TYPE_TEXT],
                'sort_order' => ['type' => FieldInterface::TYPE_INTEGER],
                'swatch' => $this->swatchMapping->get(),
            ]
        ];
    }
}

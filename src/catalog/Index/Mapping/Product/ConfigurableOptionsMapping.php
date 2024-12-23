<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Index\Mapping\Product;

use StreamX\ConnectorCatalog\Index\Mapping\Attribute\SwatchMapping;
use StreamX\ConnectorCatalog\Index\Mapping\FieldMappingInterface;
use StreamX\ConnectorCore\Api\Mapping\FieldInterface;

class ConfigurableOptionsMapping implements FieldMappingInterface
{
    private SwatchMapping $swatchMapping;

    public function __construct(SwatchMapping $swatchMapping)
    {
        $this->swatchMapping = $swatchMapping;
    }

    public function get(): array
    {
        return [
            'properties' => [
                'label' => ['type' => FieldInterface::TYPE_TEXT],
                'id' => ['type' => FieldInterface::TYPE_LONG],
                'product_id' => ['type' => FieldInterface::TYPE_LONG],
                'attribute_code' => ['type' => FieldInterface::TYPE_TEXT],
                'attribute_id' => ['type' => FieldInterface::TYPE_LONG],
                'position' => ['type' => FieldInterface::TYPE_LONG],
                'values' => [
                    'properties' => [
                        'value_index' => ['type' => FieldInterface::TYPE_KEYWORD],
                        'label' => ['type' => FieldInterface::TYPE_TEXT],
                        'swatch' => $this->swatchMapping->get(),
                    ],
                ],
            ],
        ];
    }
}

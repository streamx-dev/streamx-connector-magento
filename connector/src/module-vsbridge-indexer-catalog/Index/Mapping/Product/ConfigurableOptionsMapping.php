<?php declare(strict_types=1);

namespace Divante\VsbridgeIndexerCatalog\Index\Mapping\Product;

use Divante\VsbridgeIndexerCatalog\Index\Mapping\Attribute\SwatchMapping;
use Divante\VsbridgeIndexerCatalog\Index\Mapping\FieldMappingInterface;
use Divante\VsbridgeIndexerCore\Api\Mapping\FieldInterface;

class ConfigurableOptionsMapping implements FieldMappingInterface
{
    /**
     * @var SwatchMapping
     */
    private $swatchMapping;

    public function __construct(SwatchMapping $swatchMapping)
    {
        $this->swatchMapping = $swatchMapping;
    }

    /**
     * @inheritdoc
     */
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

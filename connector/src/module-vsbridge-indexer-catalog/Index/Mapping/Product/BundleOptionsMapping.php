<?php declare(strict_types=1);

namespace Divante\VsbridgeIndexerCatalog\Index\Mapping\Product;

use Divante\VsbridgeIndexerCatalog\Index\Mapping\FieldMappingInterface;
use Divante\VsbridgeIndexerCore\Api\Mapping\FieldInterface;

class BundleOptionsMapping implements FieldMappingInterface
{
    /**
     * @inheritdoc
     */
    public function get(): array
    {
        return [
            'properties' => [
                'option_id' => ['type' => FieldInterface::TYPE_LONG],
                'position' => ['type' => FieldInterface::TYPE_LONG],
                'title' => ['type' => FieldInterface::TYPE_TEXT],
                'sku' => ['type' => FieldInterface::TYPE_KEYWORD],
                'product_links' => [
                    'properties' => [
                        'id' => ['type' => FieldInterface::TYPE_LONG],
                        'is_default' => ['type' => FieldInterface::TYPE_BOOLEAN],
                        'qty' => ['type' => FieldInterface::TYPE_DOUBLE],
                        'can_change_quantity' => ['type' => FieldInterface::TYPE_BOOLEAN],
                        'price' => ['type' => FieldInterface::TYPE_DOUBLE],
                        'price_type' => ['type' => FieldInterface::TYPE_TEXT],
                        'position' => ['type' => FieldInterface::TYPE_LONG],
                        'sku' => ['type' => FieldInterface::TYPE_KEYWORD],
                    ],
                ],
            ]
        ];
    }
}

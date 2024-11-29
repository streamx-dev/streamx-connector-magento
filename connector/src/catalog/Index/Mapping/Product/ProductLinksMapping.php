<?php declare(strict_types=1);

namespace Divante\VsbridgeIndexerCatalog\Index\Mapping\Product;

use Divante\VsbridgeIndexerCatalog\Index\Mapping\FieldMappingInterface;
use Divante\VsbridgeIndexerCore\Api\Mapping\FieldInterface;

class ProductLinksMapping implements FieldMappingInterface
{
    /**
     * @inheritdoc
     */
    public function get(): array
    {
        return [
            'properties' => [
                'linked_product_type' => ['type' => FieldInterface::TYPE_TEXT],
                'linked_product_sku' => ['type' => FieldInterface::TYPE_KEYWORD],
                'sku' => ['type' => FieldInterface::TYPE_KEYWORD],
                'position' => ['type' => FieldInterface::TYPE_LONG],
            ],
        ];
    }
}

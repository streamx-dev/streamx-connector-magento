<?php declare(strict_types=1);

namespace Divante\VsbridgeIndexerCatalog\Index\Mapping\Product;

use Divante\VsbridgeIndexerCatalog\Index\Mapping\FieldMappingInterface;
use Divante\VsbridgeIndexerCore\Api\Mapping\FieldInterface;

/**
 * Class TierPricesMapping
 */
class TierPricesMapping implements FieldMappingInterface
{
    /**
     * @inheritdoc
     */
    public function get(): array
    {
        return [
            'properties' => [
                'customer_group_d' => ['type' => FieldInterface::TYPE_INTEGER],
                'qty' => ['type' => FieldInterface::TYPE_DOUBLE],
                'value' => ['type' => FieldInterface::TYPE_DOUBLE],
                'extension_attributes' => [
                    'properties' => [
                        'website_id' => ['type' => FieldInterface::TYPE_SHORT]
                    ],
                ],
            ],
        ];
    }
}

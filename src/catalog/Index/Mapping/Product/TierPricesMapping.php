<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Index\Mapping\Product;

use StreamX\ConnectorCatalog\Index\Mapping\FieldMappingInterface;
use StreamX\ConnectorCore\Api\Mapping\FieldInterface;

class TierPricesMapping implements FieldMappingInterface
{
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

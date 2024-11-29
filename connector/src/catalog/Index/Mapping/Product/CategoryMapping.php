<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Index\Mapping\Product;

use StreamX\ConnectorCatalog\Index\Mapping\FieldMappingInterface;
use StreamX\ConnectorCore\Api\Mapping\FieldInterface;

class CategoryMapping implements FieldMappingInterface
{
    /**
     * @inheritdoc
     */
    public function get(): array
    {
        return  [
            'type' => 'nested',
            'properties' => [
                'category_id' => ['type' => FieldInterface::TYPE_LONG],
                'position' => ['type' => FieldInterface::TYPE_LONG],
                'name' => [
                    'type' => FieldInterface::TYPE_TEXT,
                    'fields' => [
                        'keyword' => [
                            'type' => FieldInterface::TYPE_KEYWORD,
                            'ignore_above' => 256,
                        ]
                    ],
                ],
            ],
        ];
    }
}

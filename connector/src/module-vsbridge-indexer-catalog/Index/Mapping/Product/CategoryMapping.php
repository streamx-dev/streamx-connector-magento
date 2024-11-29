<?php declare(strict_types=1);

namespace Divante\VsbridgeIndexerCatalog\Index\Mapping\Product;

use Divante\VsbridgeIndexerCatalog\Index\Mapping\FieldMappingInterface;
use Divante\VsbridgeIndexerCore\Api\Mapping\FieldInterface;

/**
 * Class CategoryMapping
 */
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

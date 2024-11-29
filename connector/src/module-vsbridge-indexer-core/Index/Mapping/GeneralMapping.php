<?php

namespace Divante\VsbridgeIndexerCore\Index\Mapping;

use Divante\VsbridgeIndexerCore\Api\Mapping\FieldInterface;

/**
 * Class GeneralMapping
 */
class GeneralMapping
{
    /**
     * @var array
     */
    private $commonProperties = [
        'position' => ['type' => FieldInterface::TYPE_LONG],
        'level' => ['type' => FieldInterface::TYPE_INTEGER],
        'created_at' => [
            'type' => FieldInterface::TYPE_DATE,
            'format' => FieldInterface::DATE_FORMAT,
        ],
        'updated_at' => [
            'type' => FieldInterface::TYPE_DATE,
            'format' => FieldInterface::DATE_FORMAT,
        ]
    ];

    /**
     * @return array
     */
    public function getCommonProperties()
    {
        return $this->commonProperties;
    }
}

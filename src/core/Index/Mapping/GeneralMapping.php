<?php

namespace StreamX\ConnectorCore\Index\Mapping;

use StreamX\ConnectorCore\Api\Mapping\FieldInterface;

class GeneralMapping {

    private array $commonProperties = [
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

    public function getCommonProperties(): array {
        return $this->commonProperties;
    }
}

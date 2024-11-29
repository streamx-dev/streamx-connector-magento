<?php

declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Index\Mapping\Product;

use StreamX\ConnectorCatalog\Index\Mapping\Attribute\OptionMapping;
use StreamX\ConnectorCatalog\Index\Mapping\FieldMappingInterface;
use StreamX\ConnectorCore\Api\Mapping\FieldInterface;

class AttributesMetaMapping implements FieldMappingInterface
{
    /**
     * @var OptionMapping
     */
    private $optionMapping;

    public function __construct(OptionMapping $optionMapping)
    {
        $this->optionMapping = $optionMapping;
    }

    public function get(): array
    {
        return [
            'properties' => [
                'id' => ['type' => FieldInterface::TYPE_INTEGER],
                'attribute_id' => ['type' => FieldInterface::TYPE_INTEGER],
                'default_frontend_label' => ['type' => FieldInterface::TYPE_TEXT],
                'is_visible_on_front' => ['type' => FieldInterface::TYPE_BOOLEAN],
                'is_visible'  => ['type' => FieldInterface::TYPE_BOOLEAN],
                'frontend_input' => ['type' => FieldInterface::TYPE_TEXT],
                'is_user_defined' => ['type' => FieldInterface::TYPE_BOOLEAN],
                'is_comparable' => ['type' => FieldInterface::TYPE_BOOLEAN],
                'attribute_code' => ['type' => FieldInterface::TYPE_TEXT],
                'options' => $this->optionMapping->get(),
            ],
        ];
    }
}

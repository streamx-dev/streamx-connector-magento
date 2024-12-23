<?php

namespace StreamX\ConnectorCatalog\Index\Mapping;

use StreamX\ConnectorCatalog\Index\Mapping\Attribute\OptionMapping;
use StreamX\ConnectorCore\Api\MappingInterface;
use StreamX\ConnectorCore\Api\Mapping\FieldInterface;

class Attribute implements MappingInterface
{
    private OptionMapping $optionMapping;

    public function __construct(OptionMapping $optionMapping)
    {
        $this->optionMapping = $optionMapping;
    }

    private array $booleanProperties = [
        'is_required',
        'is_user_defined',
        'is_unique',
        'is_global',
        'is_visible',
        'is_searchable',
        'is_comparable',
        'is_visible_on_front',
        'is_html_allowed_on_front',
        'is_used_for_price_rules',
        'is_filterable_in_search',
        'used_in_product_listing',
        'used_for_sort_by',
        'is_configurable',
        'is_visible_in_advanced_search',
        'is_wysiwyg_enabled',
        'is_used_for_promo_rules',
    ];

    private array $longProperties = [
        'attribute_id',
        'id',
        'search_weight',
        'entity_type_id',
        'position',
    ];

    private array $integerProperties = [
        'is_filterable',
    ];

    private array $stringProperties  = [
        'attribute_code',
        'swatch_input_type',
        'attribute_model',
        'backend_model',
        'backend_table',
        'apply_to',
        'frontend_model',
        'frontend_input',
        'frontend_label',
        'frontend_class',
        'source_model',
        'default_value',
        'frontend_input_renderer',
    ];

    public function getMappingProperties(): array
    {
        $properties = [];

        foreach ($this->booleanProperties as $property) {
            $properties[$property] = ['type' => FieldInterface::TYPE_BOOLEAN];
        }

        foreach ($this->longProperties as $property) {
            $properties[$property] = ['type' => FieldInterface::TYPE_LONG];
        }

        foreach ($this->integerProperties as $property) {
            $properties[$property] = ['type' => FieldInterface::TYPE_INTEGER];
        }

        foreach ($this->stringProperties as $property) {
            $properties[$property] = ['type' => FieldInterface::TYPE_TEXT];
        }

        $properties['options'] = $this->optionMapping->get();

        return ['properties' => $properties];
    }
}

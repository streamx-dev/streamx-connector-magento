<?php

declare(strict_types = 1);

namespace StreamX\ConnectorCatalog\Model\ResourceModel\AttributesMetadata;

class GetAttributeFields
{
    private array $requiredColumns = [
        'is_visible_on_front',
        'is_visible',
        'attribute_id',
        'entity_type_id',
        'frontend_input',
        'attribute_id',
        'frontend_input',
        'is_user_defined',
        'is_comparable',
        'source_model',
        'attribute_code',
    ];

    public function execute(array $row): array
    {
        $attribute['id'] = (int)$row['attribute_id'];
        $attribute['default_frontend_label'] = $row['frontend_label'];

        foreach ($this->requiredColumns as $column) {
            $attribute[$column] = $row[$column];
        }

        return $attribute;
    }
}


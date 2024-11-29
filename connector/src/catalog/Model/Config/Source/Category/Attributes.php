<?php

declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Config\Source\Category;

use Magento\Eav\Model\Entity\Attribute;

class Attributes extends AbstractAttributeSource
{
    /**
     *
     */
    const RESTRICTED_ATTRIBUTES = [
        'all_children',
        'children',
        'children_count',
        'url_path',
        'url_key',
        'name',
        'is_active',
        'level',
        'path_in_store',
        'path',
        'position',
    ];

    /**
     * @inheritDoc
     */
    public function canAddAttribute(Attribute $attribute): bool
    {
        return !in_array($attribute->getAttributeCode(), self::RESTRICTED_ATTRIBUTES);
    }
}

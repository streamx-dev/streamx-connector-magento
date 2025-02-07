<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use InvalidArgumentException;
use Magento\Catalog\Model\Product\Visibility;

/**
 * This class handles options for attributes that don't use eav_attribute_option table to store options,
 * but instead hold them hardcoded in Magento source code
 */
class SpecialAttributes
{
    private const SPECIAL_ATTRIBUTES = [
        'visibility'
    ];

    public static function isSpecialAttribute(string $attributeCode): bool {
        return in_array($attributeCode, self::SPECIAL_ATTRIBUTES);
    }

    public static function getOptionsArray(string $attributeCode): array {
        if ($attributeCode === 'visibility') {
            return Visibility::getAllOptions();
        }
        throw new InvalidArgumentException("Not implemented for attribute $attributeCode");
    }

    public static function getAttributeValueLabel(string $attributeCode, string $attributeNumericValue): string {
        if ($attributeCode === 'visibility') {
            return Visibility::getOptionText(intval($attributeNumericValue)) ?? $attributeNumericValue;
        }
        throw new InvalidArgumentException("Not implemented for attribute $attributeCode");
    }

}
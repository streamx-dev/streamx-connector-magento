<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use InvalidArgumentException;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\Phrase;

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
            return array_map(function ($option) {

                /** @var $id int */
                $id = $option['value'];

                /** @var $value Phrase */
                $value = $option['label'];

                return [
                    'id' => $id,
                    'value' => $value->render()
                ];
            }, Visibility::getAllOptions());
        }
        throw new InvalidArgumentException("Not implemented for attribute $attributeCode");
    }

    public static function getAttributeValueLabel(string $attributeCode, int $attributeNumericValue): string {
        if ($attributeCode === 'visibility') {
            /** @var $optionText Phrase */
            $optionText = Visibility::getOptionText($attributeNumericValue);
            return $optionText ? $optionText->render() : (string) $attributeNumericValue;
        }
        throw new InvalidArgumentException("Not implemented for attribute $attributeCode");
    }

}
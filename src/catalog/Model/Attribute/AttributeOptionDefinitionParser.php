<?php declare(strict_types = 1);

namespace StreamX\ConnectorCatalog\Model\Attribute;

use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection as OptionCollection;
use Magento\Framework\DataObject;
use Magento\Swatches\Model\Swatch;
use StreamX\ConnectorCatalog\Model\Attributes\AttributeOptionDefinition;
use StreamX\ConnectorCatalog\Model\Attributes\AttributeOptionSwatchDefinition;

class AttributeOptionDefinitionParser
{
    /**
     * @return AttributeOptionDefinition[]
     */
    public static function parseToArray(OptionCollection $options, bool $loadSwatches): array
    {
        $res = [];
        foreach ($options as $option) {
            $res[] = self::parse($option, $loadSwatches);
        }
        return $res;
    }

    private static function parse(DataObject $option, bool $loadSwatches): AttributeOptionDefinition
    {
        $id = (int)$option->getData('option_id');
        $value = (string)$option->getData('value');
        $swatch = $loadSwatches ?
            new AttributeOptionSwatchDefinition(
                self::getSwatchTypeAsString((int)$option->getData('swatch_type')),
                $option->getData('swatch_value')
            ) : null;

        return new AttributeOptionDefinition($id, $value, $swatch);
    }

    private static function getSwatchTypeAsString(int $swatchType): string
    {
        switch ($swatchType) {
            case Swatch::SWATCH_TYPE_TEXTUAL:
                return 'textual';
            case Swatch::SWATCH_TYPE_VISUAL_COLOR:
                return 'color';
            case Swatch::SWATCH_TYPE_VISUAL_IMAGE:
                return 'image';
            case Swatch::SWATCH_TYPE_EMPTY:
                return 'empty';
            default:
                return '';
        }
    }
}

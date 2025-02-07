<?php

declare(strict_types = 1);

namespace StreamX\ConnectorCatalog\Model\Attribute;

use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection as OptionCollection;
use Magento\Swatches\Model\Swatch;

class OptionCollectionToArray
{
    public static function execute(OptionCollection $collection, bool $loadSwatches): array
    {
        $res = [];

        foreach ($collection as $item) {
            $data = [];

            $data['value'] = (string)$item->getData('option_id');
            $data['label'] = $item->getData('value');

            if ($loadSwatches) {
                $data['swatch'] = [
                    'type' => (int)$item->getData('swatch_type'),
                    'type_string' => self::getSwatchTypeAsString((int)$item->getData('swatch_type')),
                    'value' => $item->getData('swatch_value')
                ];
            }

            $res[] = $data;
        }

        return $res;
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

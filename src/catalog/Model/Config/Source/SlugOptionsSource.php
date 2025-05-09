<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class SlugOptionsSource implements OptionSourceInterface {

    public const NAME_AND_ID = 0;
    public const URL_KEY = 1;
    public const URL_KEY_AND_ID = 2;

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array {
        return [
            [
                'value' => self::NAME_AND_ID,
                'label' => 'Use Name and ID'
            ],
            [
                'value' => self::URL_KEY,
                'label' => 'Use Catalog Url Key'
            ],
            [
                'value' => self::URL_KEY_AND_ID,
                'label' => 'Use Catalog Url Key and ID'
            ]
        ];
    }

}

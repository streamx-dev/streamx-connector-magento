<?php

declare(strict_types = 1);

namespace Divante\VsbridgeIndexerCatalog\Model\Attribute;

/**
 * Class LoadOptionById
 */
class LoadOptionById
{

    /**
     * @var LoadOptions
     */
    private $loadOptions;

    /**
     * LoadOptionById constructor.
     *
     * @param LoadOptions $loadOptions
     */
    public function __construct(LoadOptions $loadOptions)
    {
        $this->loadOptions = $loadOptions;
    }

    /**
     * @param string $attributeCode
     * @param int $optionId
     * @param int $storeId
     *
     * @return array
     */
    public function execute(string $attributeCode, int $optionId, int $storeId): array
    {
        $options = $this->loadOptions->execute($attributeCode, $storeId);

        foreach ($options as $option) {
            if ($optionId === (int)$option['value']) {
                return $option;
            }
        }

        return [];
    }
}

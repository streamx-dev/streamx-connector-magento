<?php

declare(strict_types = 1);

namespace Divante\VsbridgeIndexerCatalog\Model\Attribute;

class LoadOptionById
{

    /**
     * @var LoadOptions
     */
    private $loadOptions;

    public function __construct(LoadOptions $loadOptions)
    {
        $this->loadOptions = $loadOptions;
    }

    /**
     * @param string $attributeCode
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

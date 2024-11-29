<?php

namespace Divante\VsbridgeIndexerCatalog\Model\Indexer\DataProvider\Product\Configurable;

use Divante\VsbridgeIndexerCatalog\Model\Attribute\LoadOptionById;
use Divante\VsbridgeIndexerCatalog\Model\Attribute\SortValues;
use Divante\VsbridgeIndexerCatalog\Api\CatalogConfigurationInterface;

class LoadConfigurableOptions
{
    /**
     * @var CatalogConfigurationInterface
     */
    private $catalogSettings;

    /**
     * @var LoadOptionById
     */
    private $loadOptionById;

    /**
     * @var SortValues
     */
    private $sortValues;

    public function __construct(
        LoadOptionById $loadOptionById,
        SortValues $sortValues,
        CatalogConfigurationInterface $catalogSettings
    ) {
        $this->loadOptionById = $loadOptionById;
        $this->catalogSettings = $catalogSettings;
        $this->sortValues = $sortValues;
    }

    /**
     * @param string $attributeCode
     *
     * @return array
     */
    public function execute(string $attributeCode, int $storeId, array $configurableChildren): array
    {
        $values = [];

        foreach ($configurableChildren as $child) {
            if (isset($child[$attributeCode])) {
                $value = $child[$attributeCode];

                if (isset($value)) {
                    $values[] = (int) $value;
                }
            }
        }

        $values = array_values(array_unique($values));
        $options = [];

        foreach ($values as $value) {
            $option = $this->loadOptionById->execute($attributeCode, $value, $storeId);

            if (!empty($option)) {
                if (!$this->catalogSettings->addSwatchesToConfigurableOptions()) {
                    unset($option['swatch']);
                }

                $options[] = $option;
            }
        }

        return $this->sortValues->execute($options);
    }
}

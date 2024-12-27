<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\Configurable;

use StreamX\ConnectorCatalog\Model\Attribute\LoadOptionById;
use StreamX\ConnectorCatalog\Model\Attribute\SortValues;
use StreamX\ConnectorCatalog\Api\CatalogConfigurationInterface;

class LoadConfigurableOptions
{
    private CatalogConfigurationInterface $catalogSettings;
    private LoadOptionById $loadOptionById;
    private SortValues $sortValues;

    public function __construct(
        LoadOptionById $loadOptionById,
        SortValues $sortValues,
        CatalogConfigurationInterface $catalogSettings
    ) {
        $this->loadOptionById = $loadOptionById;
        $this->catalogSettings = $catalogSettings;
        $this->sortValues = $sortValues;
    }

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

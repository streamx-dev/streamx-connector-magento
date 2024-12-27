<?php

declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Config\Source\Product;

use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Framework\Option\ArrayInterface;

class ProductType implements ArrayInterface
{
    private ConfigInterface $config;
    private ?array $types = null;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        $options = [];

        foreach ($this->getProductTypes() as $typeId => $type) {
            $options[] = [
                'value' => $typeId,
                'label' => (string)$type['label']
            ];
        }

        return $options;
    }

    private function getProductTypes(): array
    {
        if ($this->types === null) {
            $productTypes = $this->config->getAll();

            foreach ($productTypes as $productTypeKey => $productTypeConfig) {
                $productTypes[$productTypeKey]['label'] = __($productTypeConfig['label']);
            }

            $this->types = $productTypes;
        }

        return $this->types;
    }
}

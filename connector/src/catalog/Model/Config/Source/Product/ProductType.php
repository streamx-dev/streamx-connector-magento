<?php

declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Config\Source\Product;

use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Framework\Option\ArrayInterface;

class ProductType implements ArrayInterface
{
    /**
     * @var \Magento\Catalog\Model\ProductTypes\ConfigInterface
     */
    private $config;

    /**
     * @var array
     */
    private $types;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];

        foreach ($this->getTypes() as $typeId => $type) {
            $options[] = [
                'value' => $typeId,
                'label' => (string)$type['label']
            ];
        }

        return $options;
    }

    /**
     * Retrieve Product Types
     */
    private function getTypes(): array
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

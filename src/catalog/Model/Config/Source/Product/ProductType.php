<?php

declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Config\Source\Product;

use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Framework\Option\ArrayInterface;

class ProductType implements ArrayInterface
{
    private ConfigInterface $config;
    private array $types = [];
    private const UNSUPPORTED_TYPES = [
        'downloadable'
    ];

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
        if (empty($this->types)) {
            $this->types = $this->initProductTypes();
        }

        return $this->types;
    }

    private function initProductTypes(): array
    {
        $productTypes = $this->config->getAll();

        foreach ($productTypes as $productTypeKey => $productTypeConfig) {
            if (in_array($productTypeConfig['name'], self::UNSUPPORTED_TYPES)) {
                unset($productTypes[$productTypeKey]);
            } else {
                $productTypes[$productTypeKey]['label'] = __($productTypeConfig['label']);
            }
        }

        return $productTypes;
    }
}

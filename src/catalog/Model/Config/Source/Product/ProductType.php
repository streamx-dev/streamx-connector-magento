<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Config\Source\Product;

use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Framework\Data\OptionSourceInterface;

class ProductType implements OptionSourceInterface
{
    private ConfigInterface $config;
    private array $types = [];
    private const SUPPORTED_TYPES = [
        'simple',
        'configurable',
        'grouped'
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
        if (empty($this->types)) {
            $this->types[] = [
                'value' => '',
                'label' => __('[None]'),
            ];

            $allProductTypes = $this->config->getAll();
            foreach ($allProductTypes as $productTypeKey => $productTypeConfig) {
                if (in_array($productTypeKey, self::SUPPORTED_TYPES)) {
                    $this->types[] = [
                        'value' => $productTypeKey,
                        'label' => __($productTypeConfig['label'])
                    ];
                }
            }
        }

        return $this->types;
    }
}

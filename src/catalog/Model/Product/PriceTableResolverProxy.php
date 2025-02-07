<?php declare(strict_types = 1);

namespace StreamX\ConnectorCatalog\Model\Product;

use Magento\Customer\Model\Indexer\CustomerGroupDimensionProvider;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\Indexer\WebsiteDimensionProvider;

class PriceTableResolverProxy
{
    const DEFAULT_PRICE_INDEXER_TABLE = 'catalog_product_index_price';

    private ObjectManagerInterface $objectManager;

    /**
     * available from Magento 2.6
     * @var \Magento\Framework\Indexer\DimensionFactory
     */
    private $dimensionFactory;

    /**
     * available from Magento 2.6
     * @var \Magento\Catalog\Model\Indexer\Product\Price\PriceTableResolver
     */
    private $priceTableResolver;

    private array $priceIndexTableName = [];

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function resolve(int $websiteId, int $customerGroupId): string
    {
        if (class_exists('\Magento\Catalog\Model\Indexer\Product\Price\PriceTableResolver')) {
            $this->createDimensionFactory();
            $this->createPriceTableResolver();
            $key = $websiteId . '_' . $customerGroupId;

            if (!isset($this->priceIndexTableName[$key])) {
                $priceIndexTableName = $this->priceTableResolver->resolve(
                    self::DEFAULT_PRICE_INDEXER_TABLE,
                    [
                        $this->dimensionFactory->create(
                            WebsiteDimensionProvider::DIMENSION_NAME,
                            (string)$websiteId
                        ),
                        $this->dimensionFactory->create(
                            CustomerGroupDimensionProvider::DIMENSION_NAME,
                            (string)$customerGroupId
                        ),
                    ]
                );

                $this->priceIndexTableName[$key] = $priceIndexTableName;
            }

            return $this->priceIndexTableName[$key];
        }

        return self::DEFAULT_PRICE_INDEXER_TABLE;
    }

    private function createDimensionFactory(): void
    {
        if (null === $this->dimensionFactory) {
            $this->dimensionFactory = $this->create(\Magento\Framework\Indexer\DimensionFactory::class);
        }
    }

    private function createPriceTableResolver(): void
    {
        if (null === $this->priceTableResolver) {
            $this->priceTableResolver = $this->create(\Magento\Catalog\Model\Indexer\Product\Price\PriceTableResolver::class);
        }
    }

    /**
     * @return mixed
     */
    private function create(string $class)
    {
        return $this->objectManager->create($class);
    }
}

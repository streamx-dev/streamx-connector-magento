<?php declare(strict_types=1);

namespace Divante\VsbridgeIndexerCatalog\Model\Indexer\DataProvider\Product;

use Divante\VsbridgeIndexerCore\Api\DataProviderInterface;
use Divante\VsbridgeIndexerCatalog\Api\CatalogConfigurationInterface;
use Divante\VsbridgeIndexerCatalog\Model\Product\ParentResolver;

/**
 * Responsible for adding "parent_sku" for products
 */
class ParentSku implements DataProviderInterface
{
    /**
     * @const string
     */
    const FIELD_NAME = 'parent_sku';

    /**
     * @var ParentResolver
     */
    private $parentResolver;

    /**
     * @var CatalogConfigurationInterface
     */
    private $configSettings;

    public function __construct(
        ParentResolver $parentResolver,
        CatalogConfigurationInterface $configSettings
    ) {
        $this->parentResolver = $parentResolver;
        $this->configSettings = $configSettings;
    }

    /**
     * @inheritDoc
     */
    public function addData(array $indexData, int $storeId): array
    {
        if (!$this->configSettings->addParentSku()) {
            return $indexData;
        }

        $childIds = array_keys($indexData);
        $this->parentResolver->load($childIds);

        foreach ($indexData as $productId => $productDTO) {
            $productDTO[self::FIELD_NAME] = $this->parentResolver->resolveParentSku($productId);
            $indexData[$productId] = $productDTO;
        }

        return $indexData;
    }
}

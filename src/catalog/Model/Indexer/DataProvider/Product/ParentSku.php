<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use StreamX\ConnectorCore\Api\DataProviderInterface;
use StreamX\ConnectorCatalog\Api\CatalogConfigurationInterface;
use StreamX\ConnectorCatalog\Model\Product\ParentResolver;

/**
 * Responsible for adding "parent_sku" for products
 */
class ParentSku implements DataProviderInterface
{
    private const FIELD_NAME = 'parent_sku';

    private ParentResolver $parentResolver;
    private CatalogConfigurationInterface $configSettings;

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

<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use Exception;
use StreamX\ConnectorCore\Api\DataProviderInterface;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\Configurable as ConfigurableResource;

class ConfigurableData implements DataProviderInterface
{
    private ConfigurableResource $configurableResource;
    private ChildProductAttributeData $childProductAttributeDataProvider;

    /** @var DataProviderInterface[] */
    private array $dataProviders;

    public function __construct(
        ConfigurableResource $configurableResource,
        ChildProductAttributeData $childProductAttributeDataProvider,
        MediaGalleryData $mediaGalleryDataProvider,
        PriceData $priceData,
        QuantityData $quantityDataProvider,
        ChildProductDataCleaner $dataCleaner
    ) {
        $this->configurableResource = $configurableResource;
        $this->childProductAttributeDataProvider = $childProductAttributeDataProvider;
        $this->dataProviders = [
            $childProductAttributeDataProvider,
            $mediaGalleryDataProvider,
            $priceData,
            $quantityDataProvider,
            $dataCleaner
        ];
    }

    /**
     * @inheritdoc
     */
    public function addData(array &$indexData, int $storeId): void
    {
        $this->configurableResource->clear();
        $this->configurableResource->setProducts($indexData);
        $this->addBasicChildVariantsInfo($indexData, $storeId);

        $configurableChildrenAttributes = $this->configurableResource->getConfigurableAttributeCodes();
        $this->childProductAttributeDataProvider->setAdditionalAttributesToIndex($configurableChildrenAttributes);

        foreach ($indexData as &$product) {
            if (isset($product['variants'])) {
                $this->addDataFromProviders($product['variants'], $storeId);
            } else {
                $product['variants'] = [];
            }
        }

        $this->configurableResource->clear();
    }

    private function addDataFromProviders(array &$childProducts, int $storeId): void {
        $productsMap = [];
        foreach ($childProducts as $product) {
            $productsMap[$product['id']] = $product;
        }
        foreach ($this->dataProviders as $dataProvider) {
            $dataProvider->addData($productsMap, $storeId);
        }
        $childProducts = array_values($productsMap);
    }

    /**
     * @throws Exception
     */
    private function addBasicChildVariantsInfo(array &$indexData, int $storeId): void
    {
        $allChildren = $this->configurableResource->getSimpleProducts($storeId);

        if (null === $allChildren) {
            return;
        }

        foreach ($allChildren as $child) {
            $child['id'] = (int)$child['entity_id'];
            $parentIds = $child['parent_ids'];

            foreach ($parentIds as $parentId) {
                $indexData[$parentId]['variants'][] = $child;
            }
        }

        $allChildren = null;
    }
}

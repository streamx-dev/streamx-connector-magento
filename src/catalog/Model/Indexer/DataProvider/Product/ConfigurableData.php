<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use Exception;
use StreamX\ConnectorCore\Api\DataProviderInterface;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\Configurable as ConfigurableResource;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;

class ConfigurableData extends DataProviderInterface
{
    private array $childBlackListConfig = [
        'entity_id',
        'row_id',
        'type_id',
        'parent_id',
        'parent_ids',
    ];

    private ConfigurableResource $configurableResource;
    private ChildProductAttributeData $childProductAttributeDataProvider;

    /** @var DataProviderInterface[] */
    private array $dataProviders;

    public function __construct(
        ConfigurableResource $configurableResource,
        ChildProductAttributeData $childProductAttributeDataProvider,
        ChildProductMediaGalleryData $mediaGalleryDataProvider,
        QuantityData $quantityDataProvider,
        DataCleaner $dataCleaner
    ) {
        $this->configurableResource = $configurableResource;
        $this->childProductAttributeDataProvider = $childProductAttributeDataProvider;
        $this->dataProviders = [
            $childProductAttributeDataProvider,
            $mediaGalleryDataProvider,
            $quantityDataProvider,
            $dataCleaner
        ];
    }

    /**
     * @inheritdoc
     */
    public function addData(array $indexData, int $storeId): array
    {
        $this->configurableResource->clear();
        $this->configurableResource->setProducts($indexData);
        $this->addBasicChildVariantsInfo($indexData, $storeId);

        $configurableChildrenAttributes = $this->configurableResource->getConfigurableAttributeCodes($storeId);
        $this->childProductAttributeDataProvider->setAdditionalAttributesToIndex($configurableChildrenAttributes);

        $productsList = [];

        foreach ($indexData as $productId => $productDTO) {
            if (!isset($productDTO['variants'])) {
                $productDTO['variants'] = [];

                if (ConfigurableType::TYPE_CODE !== $productDTO['type_id']) {
                    $productsList[$productId] = $productDTO;
                }
                continue;
            }

            $productsList[$productId] = $productDTO;

            $childProducts = $productsList[$productId]['variants'];
            $childProducts = DataProviderInterface::addDataToEntities($childProducts, $storeId, $this->dataProviders);
            foreach ($childProducts as &$childProduct) {
                $this->removeFields($childProduct);
            }
            $productsList[$productId]['variants'] = $childProducts;
        }

        $this->configurableResource->clear();

        return $productsList;
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

    private function removeFields(array &$childData): void
    {
        foreach ($this->childBlackListConfig as $key) {
            unset($childData[$key]);
        }
    }
}

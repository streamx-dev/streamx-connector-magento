<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use Exception;
use StreamX\ConnectorCore\Api\DataProviderInterface;

use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\Configurable\LoadChildrenRawAttributes;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\Configurable\LoadConfigurableOptions;
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
    private LoadChildrenRawAttributes $childrenAttributeProcessor;
    private LoadConfigurableOptions $configurableProcessor;

    /** @var DataProviderInterface[] */
    private array $dataProviders;

    public function __construct(
        ConfigurableResource $configurableResource,
        ChildProductMediaGalleryData $mediaGalleryDataProvider,
        QuantityData $quantityDataProvider,
        LoadConfigurableOptions $configurableProcessor,
        LoadChildrenRawAttributes $childrenAttributeProcessor
    ) {
        $this->configurableResource = $configurableResource;
        $this->childrenAttributeProcessor = $childrenAttributeProcessor;
        $this->configurableProcessor = $configurableProcessor;
        $this->dataProviders = [
            $mediaGalleryDataProvider,
            $quantityDataProvider
        ];
    }

    /**
     * @inheritdoc
     */
    public function addData(array $indexData, int $storeId): array
    {
        $this->configurableResource->clear();
        $this->configurableResource->setProducts($indexData);
        $indexData = $this->prepareConfigurableChildrenAttributes($indexData, $storeId);

        $productsList = [];

        foreach ($indexData as $productId => $productDTO) {
            if (!isset($productDTO['configurable_children'])) {
                $productDTO['configurable_children'] = [];

                if (ConfigurableType::TYPE_CODE !== $productDTO['type_id']) {
                    $productsList[$productId] = $productDTO;
                }
                continue;
            }

            $productDTO = $this->applyConfigurableOptions($productDTO, $storeId);

            /**
             * Skip exporting configurable products without options
             */
            if (!empty($productDTO['configurable_options'])) {
                $productsList[$productId] = $productDTO;
            }

            $childProducts = $productsList[$productId]['configurable_children'];
            $childProducts = DataProviderInterface::addDataToEntities($childProducts, $storeId, $this->dataProviders);
            foreach ($childProducts as &$childProduct) {
                $this->removeFields($childProduct);
            }
            $productsList[$productId]['configurable_children'] = $childProducts;
        }

        $this->configurableResource->clear();

        return $productsList;
    }

    /**
     * @throws Exception
     */
    private function prepareConfigurableChildrenAttributes(array $indexData, int $storeId): array
    {
        $allChildren = $this->configurableResource->getSimpleProducts($storeId);

        if (null === $allChildren) {
            return $indexData;
        }

        $configurableAttributeCodes = $this->configurableResource->getConfigurableAttributeCodes($storeId);

        $allChildren = $this->childrenAttributeProcessor
            ->execute($storeId, $allChildren, $configurableAttributeCodes);

        foreach ($allChildren as $child) {
            $childId = $child['entity_id'];
            $child['id'] = (int) $childId;
            $parentIds = $child['parent_ids'];

            foreach ($parentIds as $parentId) {
                if (!isset($indexData[$parentId]['configurable_options'])) {
                    $indexData[$parentId]['configurable_options'] = [];
                }

                $indexData[$parentId]['configurable_children'][] = $child;
            }
        }

        $allChildren = null;

        return $indexData;
    }

    /**
     * Apply attributes to product variants + extra options for products necessary for StreamX
     *
     * @throws Exception
     */
    private function applyConfigurableOptions(array $productDTO, int $storeId): array
    {
        $configurableChildren = $productDTO['configurable_children'];
        $productAttributeOptions =
            $this->configurableResource->getProductConfigurableAttributes($productDTO, $storeId);

        $productDTO['configurable_children'] = $configurableChildren;

        foreach ($productAttributeOptions as $productAttribute) {
            $attributeCode = $productAttribute['attribute_code'];

            if (!isset($productDTO[$attributeCode . '_options'])) {
                $productDTO[$attributeCode . '_options'] = [];
            }

            $options = $this->configurableProcessor->execute(
                $attributeCode,
                $storeId,
                $configurableChildren
            );

            $values = [];

            foreach ($options as $option) {
                $values[] = (int) $option['value'];
                $optionValue = [
                    'value_index' => $option['value'],
                    'label' => $option['label'],
                ];

                if (isset($option['swatch'])) {
                    $optionValue['swatch'] = $option['swatch'];
                }

                $productAttribute['values'][] = $optionValue;
            }

            $productDTO['configurable_options'][] = $productAttribute;
            $productDTO[$productAttribute['attribute_code'] . '_options'] = $values;
        }

        return $productDTO;
    }

    private function removeFields(array &$childData): void
    {
        foreach ($this->childBlackListConfig as $key) {
            unset($childData[$key]);
        }
    }
}

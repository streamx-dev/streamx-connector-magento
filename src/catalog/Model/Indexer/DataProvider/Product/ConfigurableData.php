<?php

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use Exception;
use StreamX\ConnectorCore\Api\DataProviderInterface;
use StreamX\ConnectorCore\Indexer\DataFilter;

use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\Configurable\LoadChildrenRawAttributes;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\Configurable\LoadConfigurableOptions;
use StreamX\ConnectorCatalog\Model\LoadQuantity;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\Configurable as ConfigurableResource;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;

class ConfigurableData implements DataProviderInterface
{
    private array $childBlackListConfig = [
        'entity_id',
        'row_id',
        'type_id',
        'parent_id',
        'parent_ids',
    ];

    private DataFilter $dataFilter;
    private ConfigurableResource $configurableResource;
    private LoadQuantity $loadQuantity;
    private LoadChildrenRawAttributes $childrenAttributeProcessor;
    private LoadConfigurableOptions $configurableProcessor;

    public function __construct(
        DataFilter $dataFilter,
        ConfigurableResource $configurableResource,
        LoadQuantity $loadQuantity,
        LoadConfigurableOptions $configurableProcessor,
        LoadChildrenRawAttributes $childrenAttributeProcessor,
    ) {
        $this->dataFilter = $dataFilter;
        $this->configurableResource = $configurableResource;
        $this->loadQuantity = $loadQuantity;
        $this->childrenAttributeProcessor = $childrenAttributeProcessor;
        $this->configurableProcessor = $configurableProcessor;
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
                $productsList[$productId] = $this->prepareConfigurableProduct($productDTO);
            }
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

        $stockRowData = $this->loadQuantity->execute($allChildren, $storeId);
        $configurableAttributeCodes = $this->configurableResource->getConfigurableAttributeCodes($storeId);

        $allChildren = $this->childrenAttributeProcessor
            ->execute($storeId, $allChildren, $configurableAttributeCodes);

        foreach ($allChildren as $child) {
            $childId = $child['entity_id'];
            $child['id'] = (int) $childId;
            $parentIds = $child['parent_ids'];

            if (!isset($child['regular_price']) && isset($child['price'])) {
                $child['regular_price'] = $child['price'];
            }

            if (isset($stockRowData[$childId])) {
                $productStockData = $stockRowData[$childId];
                $child['qty'] = $productStockData['qty'];
            }

            foreach ($parentIds as $parentId) {
                $child = $this->filterData($child);

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

    private function prepareConfigurableProduct(array $productDTO): array
    {
        $configurableChildren = $productDTO['configurable_children'];
        $specialPrice = $finalPrice = $childPrice = [];

        foreach ($configurableChildren as $child) {
            if (isset($child['special_price'])) {
                $specialPrice[] = $child['special_price'];
            }

            if (isset($child['price'])) {
                $childPrice[] = $child['price'][0]; // TODO: child products don't yet have 'price' as top level field
                $finalPrice[] = $child['final_price'] ?? $child['price'];
            }
        }

        // TODO currently not required but may come back:
        // $productDTO['final_price'] = !empty($finalPrice) ? (float)min($finalPrice) : null;

        // TODO currently not required but may come back:
        // $productDTO['special_price'] = !empty($specialPrice) ? (float)min($specialPrice) : null;

        $productDTO['price'] = !empty($childPrice) ? (float)min($childPrice) : null;

        // TODO currently not required but may come back:
        // $productDTO['regular_price'] = $productDTO['price'];

        return $productDTO;
    }

    private function filterData(array $productData): array
    {
        return $this->dataFilter->execute($productData, $this->childBlackListConfig);
    }
}

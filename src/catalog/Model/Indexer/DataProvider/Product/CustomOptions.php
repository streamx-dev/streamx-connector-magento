<?php declare(strict_types = 1);

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use StreamX\ConnectorCatalog\Model\ProductMetaData;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\CustomOptions as Resource;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\CustomOptionValues as OptionValuesResource;
use StreamX\ConnectorCore\Api\DataProviderInterface;

class CustomOptions extends DataProviderInterface
{
    private Resource $optionsResourceModel;
    private OptionValuesResource $optionValuesResourceModel;
    private ProductMetaData $productMetaData;
    private CustomOptionConverter $productOptionProcessor;

    public function __construct(
        Resource $resource,
        OptionValuesResource $customOptionValues,
        CustomOptionConverter $processor,
        ProductMetaData $productMetaData
    ) {
        $this->optionsResourceModel = $resource;
        $this->optionValuesResourceModel = $customOptionValues;
        $this->productMetaData = $productMetaData;
        $this->productOptionProcessor = $processor;
    }

    /**
     * @inheritdoc
     */
    public function addData(array $indexData, int $storeId): array
    {
        $linkField = $this->productMetaData->get()->getLinkField();
        $linkFieldIds = array_column($indexData, $linkField);

        $options = $this->optionsResourceModel->loadProductOptions($linkFieldIds, $storeId);

        if (empty($options)) {
            return $indexData;
        }

        $optionIds = array_column($options, 'option_id');
        $values = $this->optionValuesResourceModel->loadOptionValues($optionIds, $storeId);

        $optionsByProduct = $this->productOptionProcessor->process($options, $values);

        foreach ($indexData as $productId => $productData) {
            $linkFieldValue = $productData[$linkField];

            if (isset($optionsByProduct[$linkFieldValue])) {
                $indexData[$productId]['custom_options'] = $optionsByProduct[$linkFieldValue];
            }
        }

        return $indexData;
    }
}

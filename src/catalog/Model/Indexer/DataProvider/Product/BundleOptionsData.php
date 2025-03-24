<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

use StreamX\ConnectorCatalog\Model\ProductMetaData;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use StreamX\ConnectorCore\Api\DataProviderInterface;
use Zend_Db_Expr;

class BundleOptionsData implements DataProviderInterface
{
    private ResourceConnection $resource;
    private ProductMetaData $productMetaData;

    public function __construct(
        ProductMetaData $productMetaData,
        ResourceConnection $resourceModel
    ) {
        $this->resource = $resourceModel;
        $this->productMetaData = $productMetaData;
    }

    /**
     * @inheritdoc
     */
    public function addData(array &$indexData, int $storeId): void
    {
        $linkField = $this->productMetaData->getLinkField();

        $products = [];
        foreach ($indexData as $product) {
            $products[$product[$linkField]] = $product;
        }

        $productBundleOptions = $this->loadBundleOptions($products, $storeId);

        foreach ($productBundleOptions as $productId => $bundleOptions) {
            $indexData[$productId]['bundle_options'] = [];

            foreach ($bundleOptions as $option) {
                $indexData[$productId]['bundle_options'][] = $option;
            }
        }
    }

    private function loadBundleOptions(array $products, int $storeId): array {
        $productIds = $this->getBundleProductIds($products);

        if (empty($productIds)) {
            return [];
        }

        $bundleOptions = $this->getBundleOptionsFromResource($productIds, $storeId);

        $bundleOptionsByProduct = [];
        foreach ($bundleOptions as $bundleOption) {
            /* entity_id or row_id*/
            $parentId = $bundleOption['parent_id'];
            $parentEntityId = $products[$parentId]['entity_id'];
            $optionId = $bundleOption['option_id'];

            $bundleOptionsByProduct[$parentEntityId][$optionId] = [
                'option_id' => (int)($bundleOption['option_id']),
                'position' => (int)($bundleOption['position']),
                'type' => $bundleOption['type'],
                'sku' => $products[$parentId]['sku'],
                'title' => $bundleOption['title'],
                'required' => (bool)$bundleOption['required'],
            ];
        }
        return $bundleOptionsByProduct;
    }

    private function getBundleProductIds(array $products): array {
        $bundleProductIds = [];

        foreach ($products as $productData) {
            if ('bundle' === $productData['type_id']) {
                $linkFieldId = $this->productMetaData->getLinkField();
                $bundleProductIds[] = $productData[$linkFieldId];
            }
        }

        return $bundleProductIds;
    }

    private function getBundleOptionsFromResource(array $productIds, int $storeId): array {
        $select = $this->getConnection()
            ->select()
            ->from(
                ['option' => $this->resource->getTableName('catalog_product_bundle_option')]
            )
            ->where('parent_id IN (?)', $productIds)
            ->order('option.position ASC')
            ->order('option.option_id ASC')
            ->joinLeft(
                ['option_value_default' => $this->resource->getTableName('catalog_product_bundle_option_value')],
                'option.option_id = option_value_default.option_id AND option_value_default.store_id = 0',
                []
            )
            ->columns(['title' => new Zend_Db_Expr("COALESCE (option_value.title, option_value_default.title)")])
            ->joinLeft(
                ['option_value' => $this->resource->getTableName('catalog_product_bundle_option_value')],
                "option.option_id = option_value.option_id AND option_value.store_id = $storeId",
                []
            );

        return $this->getConnection()->fetchAll($select);
    }

    private function getConnection(): AdapterInterface {
        return $this->resource->getConnection();
    }
}

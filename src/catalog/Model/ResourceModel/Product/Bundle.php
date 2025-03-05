<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Product;

use Exception;
use StreamX\ConnectorCatalog\Model\ProductMetaData;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;

class Bundle
{
    private ResourceConnection $resource;
    private ?array $products;
    private ?array $bundleProductIds;
    private array $bundleOptionsByProduct = [];
    private ProductMetaData $productMetaData;

    public function __construct(
        ProductMetaData $productMetaData,
        ResourceConnection $resourceModel
    ) {
        $this->resource = $resourceModel;
        $this->productMetaData = $productMetaData;
    }

    /**
     * @throws Exception
     */
    public function setProducts(array $products): void
    {
        $linkField = $this->productMetaData->getLinkField();

        foreach ($products as $product) {
            $this->products[$product[$linkField]] = $product;
        }
    }

    /**
     * Clear data
     */
    public function clear(): void
    {
        $this->products = null;
        $this->bundleOptionsByProduct = [];
        $this->bundleProductIds = null;
    }

    public function loadBundleOptions(int $storeId): array
    {
        $productIds = $this->getBundleIds();

        if (empty($productIds)) {
            return [];
        }

        $this->initOptions($storeId);

        return $this->bundleOptionsByProduct;
    }

    /**
     * Init Options
     */
    private function initOptions(int $storeId): void
    {
        $bundleOptions = $this->getBundleOptionsFromResource($storeId);

        foreach ($bundleOptions as $bundleOption) {
            /* entity_id or row_id*/
            $parentId = $bundleOption['parent_id'];
            $parentEntityId = $this->products[$parentId]['entity_id'];
            $optionId = $bundleOption['option_id'];

            $this->bundleOptionsByProduct[$parentEntityId][$optionId] = [
                'option_id' => (int)($bundleOption['option_id']),
                'position' => (int)($bundleOption['position']),
                'type' => $bundleOption['type'],
                'sku' => $this->products[$parentId]['sku'],
                'title' => $bundleOption['title'],
                'required' => (bool)$bundleOption['required'],
            ];
        }
    }

    private function getBundleOptionsFromResource(int $storeId): array
    {
        $productIds = $this->getBundleIds();

        $select = $this->getConnection()->select()->from(
            ['main_table' => $this->resource->getTableName('catalog_product_bundle_option')]
        );

        $select->where('parent_id IN (?)', $productIds);
        $select->order('main_table.position asc')
            ->order('main_table.option_id asc');

        $this->joinOptionValues($select, $storeId);

        return $this->getConnection()->fetchAll($select);
    }

    private function joinOptionValues(Select $select, int $storeId): void
    {
        $select
            ->joinLeft(
                ['option_value_default' => $this->resource->getTableName('catalog_product_bundle_option_value')],
                'main_table.option_id = option_value_default.option_id and option_value_default.store_id = 0',
                []
            )
            ->columns(['default_title' => 'option_value_default.title']);

        $title = $this->getConnection()->getCheckSql(
            'option_value.title IS NOT NULL',
            'option_value.title',
            'option_value_default.title'
        );

        $select->columns(['title' => $title])
            ->joinLeft(
                ['option_value' => $this->resource->getTableName('catalog_product_bundle_option_value')],
                $this->getConnection()->quoteInto(
                    'main_table.option_id = option_value.option_id and option_value.store_id = ?',
                    $storeId
                ),
                []
            );
    }

    /**
     * @throws Exception
     */
    private function getBundleIds(): array
    {
        if (null === $this->bundleProductIds) {
            $this->bundleProductIds = [];

            foreach ($this->products as $productData) {
                if ('bundle' === $productData['type_id']) {
                    $linkFieldId = $this->productMetaData->getLinkField();
                    $this->bundleProductIds[] = $productData[$linkFieldId];
                }
            }
        }

        return $this->bundleProductIds;
    }

    private function getConnection(): AdapterInterface
    {
        return $this->resource->getConnection();
    }
}

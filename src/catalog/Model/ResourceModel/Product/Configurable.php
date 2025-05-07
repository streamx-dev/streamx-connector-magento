<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Product;

use StreamX\ConnectorCatalog\Model\ProductMetaData;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Helper as DbHelper;

class Configurable
{
    private DbHelper $dbHelper;
    private LoadAttributes $loadAttributes;
    private ResourceConnection $resource;
    private Product $productResource;
    private ProductMetaData $productMetaData;

    /**
     * All simple products for the configurable products found in products passed by the setProducts function
     * Key: simple product id, value: array with keys: sku, entity_id, parent_ids (array with key: zero based index, value: parent product id)
     */
    private array $simpleProducts = [];

    /**
     * Key: attribute id, value: attribute code
     */
    private array $configurableAttributeCodes = [];

    public function __construct(
        LoadAttributes $loadAttributes,
        Product $productResource,
        ProductMetaData $productMetaData,
        ResourceConnection $resourceConnection,
        DbHelper $dbHelper
    ) {
        $this->loadAttributes = $loadAttributes;
        $this->resource = $resourceConnection;
        $this->productMetaData = $productMetaData;
        $this->productResource = $productResource;
        $this->dbHelper = $dbHelper;
    }

    public function setProducts(array $products, int $storeId): void
    {
        $configurableProductIds = $this->loadConfigurableProductIds($products);
        $configurableProductAttributes = $this->getConfigurableAttributesForProductsFromResource($configurableProductIds);
        $this->configurableAttributeCodes = $this->loadConfigurableAttributeCodes($configurableProductAttributes);
        $this->simpleProducts = $this->loadSimpleProducts($configurableProductIds, $storeId);
    }

    /**
     * @return array Key: configurable product id, value: attribute_ids (comma separated)
     */
    private function getConfigurableAttributesForProductsFromResource(array $configurableProductIds): array
    {
        $parentIds = array_keys($configurableProductIds);

        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from(
                $this->resource->getTableName('catalog_product_super_attribute'),
                [ 'product_id' ]
            )
            ->group('product_id')
            ->where('product_id IN (?)', $parentIds);
        $this->dbHelper->addGroupConcatColumn($select, 'attribute_ids', 'attribute_id');

        return $connection->fetchPairs($select);
    }

    private function loadConfigurableAttributeCodes(array $configurableProductAttributes): array
    {
        $configurableAttributeCodes = [];

        foreach ($configurableProductAttributes as $configurableProductId => $configurableAttributeIds) {
            $attributeIds = array_map(
                'intval',
                explode(',', $configurableAttributeIds)
            );

            foreach ($attributeIds as $attributeId) {
                $attributeModel = $this->loadAttributes->getAttributeById($attributeId);
                $configurableAttributeCodes[$attributeId] = $attributeModel->getAttributeCode();
            }
        }

        return $configurableAttributeCodes;
    }

    public function getConfigurableAttributeCodes(): array {
        return array_values($this->configurableAttributeCodes);
    }

    /**
     * Filters the products array to return array with key: configurable product link field, value: its entity id
     */
    private function loadConfigurableProductIds(array $products): array
    {
        $linkField = $this->productMetaData->getLinkField();
        $entityField = $this->productMetaData->getIdentifierField();

        $configurableProductIds = [];
        foreach ($products as $product) {
            if ($product['type_id'] == ConfigurableType::TYPE_CODE) {
                $entityId = $product[$entityField];
                $linkId = $product[$linkField];
                $configurableProductIds[$linkId] = $entityId;
            }
        }

        return $configurableProductIds;
    }

    /**
     * Return all associated simple products for the given configurable products
     */
    private function loadSimpleProducts(array $configurableProductIds, int $storeId): array
    {
        $allParentIds = array_keys($configurableProductIds);
        $childrenProducts = $this->productResource->loadChildrenProducts($allParentIds, $storeId);

        $simpleProducts = [];
        foreach ($childrenProducts as $product) {
            $simpleId = $product['entity_id'];
            $parentIds = explode(',', $product['parent_ids']);
            $parentIds = $this->mapLinkFieldToEntityId($parentIds, $configurableProductIds);
            $product['parent_ids'] = $parentIds;
            $simpleProducts[$simpleId] = $product;
        }

        return $simpleProducts;
    }

    public function getSimpleProducts(): array {
        return $this->simpleProducts;
    }

    private function mapLinkFieldToEntityId(array $linkIds, array $configurableProductIds): array
    {
        $productIds = [];

        foreach ($linkIds as $id) {
            $productIds[] = $configurableProductIds[$id];
        }

        return $productIds;
    }
}

<?php

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Product;

use Exception;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use StreamX\ConnectorCatalog\Model\ProductMetaData;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Helper as DbHelper;

class Configurable
{
    private DbHelper $dbHelper;
    private ProductAttributesProvider $attributeDataProvider;
    private ResourceConnection $resource;
    private Product $productResource;
    private ProductMetaData $productMetaData;

    /**
     * Array of the ids of configurable products from $productCollection
     */
    private ?array $configurableProductIds = null;

    /**
     * All associated simple products from configurables in $configurableProductIds
     */
    private ?array $simpleProducts = null;

    /**
     * Array keys are the configurable product ids,
     * Values: super_product_attribute_id, attribute_id, position
     */
    private ?array $configurableProductAttributes = null;
    private ?array $configurableAttributesInfo = null;
    private ?array $productsData = null;

    public function __construct(
        ProductAttributesProvider $attributeDataProvider,
        Product $productResource,
        ProductMetaData $productMetaData,
        ResourceConnection $resourceConnection,
        DbHelper $dbHelper
    ) {
        $this->attributeDataProvider = $attributeDataProvider;
        $this->resource = $resourceConnection;
        $this->productMetaData = $productMetaData;
        $this->productResource = $productResource;
        $this->dbHelper = $dbHelper;
    }

    public function clear(): void
    {
        $this->productsData = null;
        $this->configurableAttributesInfo = null;
        $this->configurableProductAttributes = null;
        $this->simpleProducts = null;
        $this->configurableProductIds = null;
    }

    public function setProducts(array $products): void
    {
        $this->productsData = $products;
    }

    /**
     * Load all configurable attributes used in the current product collection.
     */
    private function getConfigurableProductAttributes(): array
    {
        if (!$this->configurableProductAttributes) {
            $productIds = $this->getParentIds();
            $attributes = $this->getConfigurableAttributesForProductsFromResource($productIds);
            $this->configurableProductAttributes = $attributes;
        }

        return $this->configurableProductAttributes;
    }

    private function getConfigurableAttributesForProductsFromResource(array $productIds): array
    {
        $select = $this->getConnection()->select()
            ->from(
                $this->resource->getTableName('catalog_product_super_attribute'),
                [
                    'product_id',
                    'product_super_attribute_id',
                ]
            )
            ->group('product_id')
            ->where('product_id IN (?)', $productIds);
        $this->dbHelper->addGroupConcatColumn($select, 'attribute_ids', 'attribute_id');

        return $this->getConnection()->fetchAssoc($select);
    }

    /**
     * @throws Exception
     */
    public function getConfigurableAttributeCodes(int $storeId): array
    {
        $attributes = $this->getConfigurableAttributeFullInfo($storeId);

        return array_column($attributes, 'attribute_code');
    }

    /**
     * Return array of all configurable attributes in the current collection.
     * Array indexes are the attribute ids, array values the attribute code
     * @throws Exception
     */
    private function getConfigurableAttributeFullInfo(int $storeId): array
    {
        if (null !== $this->configurableAttributesInfo) {
            return $this->configurableAttributesInfo;
        }

        // build list of all configurable attribute codes for the current collection
        $this->configurableAttributesInfo = [];

        foreach ($this->getConfigurableProductAttributes() as $configurableAttribute) {
            $attributeIds = explode(',', $configurableAttribute['attribute_ids']);

            foreach ($attributeIds as $attributeId) {
                if ($attributeId && !isset($this->configurableAttributesInfo[$attributeId])) {
                    $attributeModel = $this->attributeDataProvider->getAttributeById($attributeId);

                    $this->configurableAttributesInfo[$attributeId] = [
                        'attribute_id' => (int)$attributeId,
                        'attribute_code' => $attributeModel->getAttributeCode(),
                        'label' => $attributeModel->getStoreLabel($storeId),
                    ];
                }
            }
        }

        return $this->configurableAttributesInfo;
    }

    /**
     * Return array of ids of configurable products in the current product collection
     */
    private function getConfigurableProductIds(): array
    {
        if (null === $this->configurableProductIds) {
            $linkField = $this->productMetaData->get()->getLinkField();
            $entityField = $this->productMetaData->get()->getIdentifierField();

            $this->configurableProductIds = [];
            $products = $this->productsData;

            foreach ($products as $product) {
                if ($product['type_id'] == ConfigurableType::TYPE_CODE) {
                    $entityId = $product[$entityField];
                    $linkId = $product[$linkField];
                    $this->configurableProductIds[$linkId] = $entityId;
                }
            }
        }

        return $this->configurableProductIds;
    }

    private function getParentIds(): array
    {
        $productIds = $this->getConfigurableProductIds();

        return array_keys($productIds);
    }

    /**
     * Return all associated simple products for the configurable products in
     * the current product collection.
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getSimpleProducts(int $storeId): ?array
    {
        if (null === $this->simpleProducts) {
            $parentIds = $this->getParentIds();
            $childrenProducts = $this->productResource->loadChildrenProducts($parentIds, $storeId);

            /** @var array $product */
            foreach ($childrenProducts as $product) {
                $simpleId = $product['entity_id'];
                $parentIds = explode(',', $product['parent_ids']);
                $parentIds = $this->mapLinkFieldToEntityId($parentIds);
                $product['parent_ids'] = $parentIds;
                $this->simpleProducts[$simpleId] = $product;
            }
        }

        return $this->simpleProducts;
    }

    private function mapLinkFieldToEntityId(array $linkIds): array
    {
        $productIds = [];

        foreach ($linkIds as $id) {
            $productIds[] = $this->configurableProductIds[$id];
        }

        return $productIds;
    }

    private function getConnection(): AdapterInterface
    {
        return $this->resource->getConnection();
    }
}

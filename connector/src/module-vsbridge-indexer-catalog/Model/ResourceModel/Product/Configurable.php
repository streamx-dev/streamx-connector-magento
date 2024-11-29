<?php

namespace Divante\VsbridgeIndexerCatalog\Model\ResourceModel\Product;

use Divante\VsbridgeIndexerCatalog\Model\ProductMetaData;
use Divante\VsbridgeIndexerCatalog\Model\ResourceModel\Product;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Helper as DbHelper;
use Psr\Log\LoggerInterface;

class Configurable
{

    /**
     * @var DbHelper
     */
    private $dbHelper;

    /**
     * @var AttributeDataProvider
     */
    private $attributeDataProvider;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var Product
     */
    private $productResource;

    /**
     * @var ProductMetaData
     */
    private $productMetaData;

    /**
     * Array of the ids of configurable products from $productCollection
     *
     * @var array
     */
    private $configurableProductIds;

    /**
     * All associated simple products from configurables in $configurableProductIds
     *
     * @var array
     */
    private $simpleProducts;

    /**
     * Array of associated simple product ids.
     * The array index are configurable product ids, the array values are
     * arrays of the associated simple product ids.
     *
     * @var array
     */
    private $associatedSimpleProducts;

    /**
     * Array keys are the configurable product ids,
     * Values: super_product_attribute_id, attribute_id, position
     *
     * @var array
     */
    private $configurableProductAttributes;

    /**
     * @var array
     */
    private $configurableAttributesInfo;

    /**
     * @var array
     */
    private $productsData;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        LoggerInterface $logger,
        AttributeDataProvider $attributeDataProvider,
        Product $productResource,
        ProductMetaData $productMetaData,
        ResourceConnection $resourceConnection,
        DbHelper $dbHelper
    ) {
        $this->attributeDataProvider = $attributeDataProvider;
        $this->resource = $resourceConnection;
        $this->productMetaData = $productMetaData;
        $this->productResource = $productResource;
        $this->logger = $logger;
        $this->dbHelper = $dbHelper;
    }

    public function clear(): void
    {
        $this->productsData = null;
        $this->associatedSimpleProducts = null;
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
     * Return the attribute values of the associated simple products
     *
     * @param array $product Configurable product.
     *
     * @throws \Exception
     */
    public function getProductConfigurableAttributes(array $product, int $storeId): array
    {
        if ($product['type_id'] != ConfigurableType::TYPE_CODE) {
            return [];
        }

        $attributeIds = $this->getProductConfigurableAttributeIds($product);

        if (empty($attributeIds)) {
            return [];
        }

        $attributes = $this->getConfigurableAttributeFullInfo($storeId);
        $productConfigAttributes = [];

        foreach ($attributeIds as $attributeId) {
            $code = $attributes[$attributeId]['attribute_code'];
            $productConfigAttributes[$code] = $attributes[$attributeId];
        }

        return $productConfigAttributes;
    }

    /**
     * Return array of configurable attribute ids of the given configurable product.
     */
    private function getProductConfigurableAttributeIds(array $product): array
    {
        $attributes = $this->getConfigurableProductAttributes();
        $linkField = $this->productMetaData->get()->getLinkField();
        $linkFieldValue = $product[$linkField];

        if (!isset($attributes[$linkFieldValue])) {
            $entityField = $this->productMetaData->get()->getIdentifierField();
            $this->logger->error(
                sprintf('Cannot find super attribute for Product %d [%s]', $linkFieldValue, $entityField)
            );

            return [];
        }

        return explode(',', $attributes[$linkFieldValue]['attribute_ids']);
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

    /**
     * This method actually would belong into a resource model, but for easier
     * reference I dropped it into the helper here.
     */
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
     * @throws \Exception
     */
    public function getConfigurableAttributeCodes(int $storeId): array
    {
        $attributes = $this->getConfigurableAttributeFullInfo($storeId);

        return array_column($attributes, 'attribute_code');
    }

    /**
     * Return array of all configurable attributes in the current collection.
     * Array indexes are the attribute ids, array values the attribute code
     * @throws \Exception
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
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSimpleProducts(int $storeId): array
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

    /**
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private function getConnection()
    {
        return $this->resource->getConnection();
    }
}

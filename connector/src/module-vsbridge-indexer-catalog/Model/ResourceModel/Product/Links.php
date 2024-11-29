<?php

namespace Divante\VsbridgeIndexerCatalog\Model\ResourceModel\Product;

use Divante\VsbridgeIndexerCatalog\Model\Product\LinkTypeMapper;
use Divante\VsbridgeIndexerCatalog\Model\ProductMetaData;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

class Links
{
    /**
     * @const string
     */
    const POSITION_ATTRIBUTE_CODE = 'position';

    /**
     * @var array
     */
    private $products = [];

    /**
     * @var array
     */
    private $links;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var LinkTypeMapper
     */
    private $linkTypeMapper;
    
    /**
     * @var ProductMetaData
     */
    private $productMetaData;

    public function __construct(
        ProductMetaData $productMetaData,
        LinkTypeMapper $linkTypeMapper,
        ResourceConnection $resourceConnection
    ) {
        $this->linkTypeMapper = $linkTypeMapper;
        $this->resource = $resourceConnection;
        $this->productMetaData = $productMetaData;
    }

    public function clear(): void
    {
        $this->links = null;
        $this->products = null;
    }

    public function setProducts(array $products): void
    {
        $linkField = $this->productMetaData->get()->getLinkField();

        foreach ($products as $product) {
            $this->products[$product[$linkField]] = $product;
        }
    }

    public function getLinkedProduct(array $product): array
    {
        $links = $this->getAllLinkedProducts();
        $productId = $product['id'];

        if (isset($links[$productId])) {
            $linkProductList = [];

            foreach ($links[$productId] as $linkData) {
                $typeId = (int)$linkData['link_type_id'];
                $linkType = $this->getLinkType($typeId);

                if ($linkType) {
                    $position = isset($linkData['position']) ? (int)$linkData['position'] : 0;
                    $linkProductList[] = [
                        'sku' => $product['sku'],
                        'link_type' => $linkType,
                        'linked_product_sku' => $linkData['sku'],
                        'linked_product_type' => $linkData['type_id'],
                        'position' => $position,
                    ];
                }
            }

            return $linkProductList;
        }

        return [];
    }

    private function getLinkType(int $typeId): ?string
    {
        return $this->linkTypeMapper->map($typeId);
    }

    private function getAllLinkedProducts(): array
    {
        if (null === $this->links) {
            $select = $this->buildLinksSelect();
            $links = $this->getConnection()->fetchAll($select);
            $groupByProduct = [];

            foreach ($links as $link) {
                $productId = $link['product_id'];
                $entityId = $this->products[$productId]['entity_id'];
                unset($link['product_id']);
                $groupByProduct[$entityId][] = $link;
            }

            $this->links = $groupByProduct;
        }

        return $this->links;
    }

    private function buildLinksSelect(): Select
    {
        $productIds = $this->getProductsIds();

        $select = $this->getConnection()->select()
            ->from(
                ['links' => $this->resource->getTableName('catalog_product_link')],
                [
                    'product_id',
                    'linked_product_id',
                    'link_type_id',
                ]
            )
            ->where('product_id in (?)', $productIds);

        $select->joinLeft(
            ['entity' => $this->resource->getTableName('catalog_product_entity')],
            'links.linked_product_id = entity.entity_id',
            [
                'sku',
                'type_id',
            ]
        );

        return $this->joinPositionAttribute($select);
    }

    private function joinPositionAttribute(Select $select): Select
    {
        $alias = 'link_position';

        $table = $this->resource->getTableName($this->getAttributeTypeTable());

        $joinCondition = [
            "{$alias}.link_id = links.link_id",
        ];

        $select->joinLeft(
            [$alias => $table],
            implode(' AND ', $joinCondition),
            [self::POSITION_ATTRIBUTE_CODE => 'value']
        );

        return $select;
    }

    /**
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private function getConnection()
    {
        return $this->resource->getConnection();
    }

    private function getAttributeTypeTable(): string
    {
        return $this->resource->getTableName('catalog_product_link_attribute_int');
    }

    /**
     * Add product filter to collection
     *
     * @return int[]
     */
    private function getProductsIds()
    {
        $products = $this->getProducts();

        return array_keys($products);
    }

    private function getProducts(): array
    {
        return $this->products;
    }
}

<?php

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Product\Type\Grouped;

use StreamX\ConnectorCatalog\Model\ProductMetaData;
use StreamX\ConnectorCatalog\Model\Product\GetParentsByChildIdInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\GroupedProduct\Model\ResourceModel\Product\Link;

class GetParentsByChildId implements GetParentsByChildIdInterface
{
    private ResourceConnection $resourceConnection;
    private ProductMetaData $productMetaData;

    public function __construct(
        ProductMetaData $productMetaData,
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->productMetaData = $productMetaData;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $childId): array
    {
        $parentSku = [];
        $connection = $this->resourceConnection->getConnection();
        $select = $this->buildSelect($childId);
        $result = $connection->fetchAll($select);
        $productIds = array_column($result, 'product_id');

        $sku = $this->getProductSkusByIds($productIds);

        foreach ($result as $row) {
            $parentSku[$row['linked_product_id']] = $parentSku[$row['linked_product_id']] ?? [];
            $parentSku[$row['linked_product_id']][] = $sku[$row['product_id']];
        }

        return $parentSku;
    }

    private function buildSelect(array $childId): Select
    {
        $connection = $this->resourceConnection->getConnection();

        return $connection->select()->from(
            $this->resourceConnection->getTableName('catalog_product_link'),
            ['product_id', 'linked_product_id']
        )->where(
            'linked_product_id IN(?)',
            $childId
        )->where(
            'link_type_id = ?',
            Link::LINK_TYPE_GROUPED
        );
    }

    /**
     * Retrieve sku for parents
     */
    private function getProductSkusByIds(array $productIds): array
    {
        $linkField = $this->productMetaData->get()->getLinkField();
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()->from('catalog_product_entity', [$linkField, 'sku'])
            ->where(sprintf('%s IN (?)', $linkField), $productIds);

        return $connection->fetchPairs($select);
    }
}

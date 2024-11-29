<?php

namespace Divante\VsbridgeIndexerCatalog\Model\ResourceModel\Product;

use Magento\Framework\App\ResourceConnection;
use Divante\VsbridgeIndexerCatalog\Model\ProductMetaData;
use Magento\UrlRewrite\Model\Storage\DbStorage;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

class Rewrite
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var ProductMetaData
     */
    private $productMetaData;

    public function __construct(
        ResourceConnection $resourceModel,
        ProductMetaData $productMetaData
    ) {
        $this->resource = $resourceModel;
        $this->productMetaData = $productMetaData;
    }

    public function getRawRewritesData(array $productIds, int $storeId): array
    {
        $connection = $this->resource->getConnection();
        $select = $this->resource->getConnection()->select();
        $select->from(
            $this->resource->getTableName(DbStorage::TABLE_NAME),
            [
                'entity_id',
                'request_path',
            ]
        );

        $select->where(
            UrlRewrite::ENTITY_TYPE . ' = ? ',
            \Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator::ENTITY_TYPE
        );
        $select->where('entity_id IN (?)', $productIds);
        $select->where('store_id = ? ', $storeId);
        $select->where('metadata IS NULL');

        return $connection->fetchPairs($select);
    }
}

<?php

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Product;

use Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator;
use Magento\Framework\App\ResourceConnection;
use Magento\UrlRewrite\Model\Storage\DbStorage;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

class Rewrite
{
    private ResourceConnection $resource;

    public function __construct(ResourceConnection $resourceModel)
    {
        $this->resource = $resourceModel;
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
            ProductUrlRewriteGenerator::ENTITY_TYPE
        );
        $select->where('entity_id IN (?)', $productIds);
        $select->where('store_id = ? ', $storeId);
        $select->where('metadata IS NULL');

        return $connection->fetchPairs($select);
    }
}

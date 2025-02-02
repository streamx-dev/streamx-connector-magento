<?php

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Product;

use Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\Customer\Model\Group;
use Magento\Framework\DB\Adapter\AdapterInterface;
use StreamX\ConnectorCatalog\Model\ProductMetaData;

class TierPrices
{
    private ResourceConnection $resource;
    private ProductMetaData $productMetaData;

    public function __construct(
        ResourceConnection $resourceModel,
        ProductMetaData $productMetaData
    ) {
        $this->resource = $resourceModel;
        $this->productMetaData = $productMetaData;
    }

    /**
     * @throws Exception
     */
    public function loadTierPrices(int $websiteId, array $linkFieldIds): array
    {
        $linkField = $this->productMetaData->get()->getLinkField();

        $columns = [
            'price_id' => 'value_id',
            'website_id' => 'website_id',
            'all_groups' => 'all_groups',
            'cust_group' => 'customer_group_id',
            'price_qty' => 'qty',
            'price' => 'value',
            $linkField => $linkField,
        ];

        $select = $this->getConnection()->select()
            ->from($this->resource->getTableName('catalog_product_entity_tier_price'), $columns)
            ->where("$linkField IN(?)", $linkFieldIds)
            ->order(
                [
                    $linkField,
                    'qty',
                ]
            );

        if ($websiteId === 0) {
            $select->where('website_id = ?', $websiteId);
        } else {
            $select->where(
                'website_id IN (?)',
                [
                    '0',
                    $websiteId,
                ]
            );
        }

        $tierPrices = [];

        foreach ($this->getConnection()->fetchAll($select) as $row) {
            $tierPrices[$row[$linkField]][] = [
                'website_id' => (int)$row['website_id'],
                'cust_group' => $row['all_groups'] ? Group::CUST_GROUP_ALL
                    : (int)$row['cust_group'],
                'price_qty' => (float)$row['price_qty'],
                'price' => (float)$row['price'],
                'website_price' => (float)$row['price'],
            ];
        }

        return $tierPrices;
    }

    private function getConnection(): AdapterInterface
    {
        return $this->resource->getConnection();
    }
}

<?php

declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Product;

use StreamX\ConnectorCatalog\Model\ResourceModel\Product;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class WebsiteSelectModifier implements BaseSelectModifierInterface
{
    private ResourceConnection $resourceConnection;
    private StoreManagerInterface $storeManager;

    public function __construct(
        ResourceConnection $resourceConnection,
        StoreManagerInterface $storeManager
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Select $select, int $storeId): Select
    {
        $connection = $select->getConnection();
        $websiteId = $this->getWebsiteId($storeId);
        $indexTable = $this->resourceConnection->getTableName('catalog_product_website');

        $conditions = [sprintf('websites.product_id = %s.entity_id', Product::MAIN_TABLE_ALIAS)];
        $conditions[] = $connection->quoteInto('websites.website_id = ?', $websiteId);

        $select->join(['websites' => $indexTable], join(' AND ', $conditions), []);

        return $select;
    }

    /**
     * Retrieve WebsiteId for given store
     *
     * @throws NoSuchEntityException
     */
    private function getWebsiteId(int $storeId): int
    {
        $store = $this->storeManager->getStore($storeId);

        return (int) $store->getWebsiteId();
    }
}

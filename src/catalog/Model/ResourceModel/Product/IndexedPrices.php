<?php declare(strict_types = 1);

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Product;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\CatalogRule\Model\ResourceModel\Rule\Product\Price as CatalogRulePrice;

use StreamX\ConnectorCatalog\Model\SystemConfig\CatalogConfig;
use StreamX\ConnectorCatalog\Model\ProductMetaData;
use StreamX\ConnectorCatalog\Model\Product\PriceTableResolverProxy;

/**
 * Only default customer Group ID (0) is supported now
 */
class IndexedPrices
{
    private const DEFAULT_CUSTOMER_GROUP_ID = 0;

    private ResourceConnection $resource;
    private CatalogConfig $settings;
    private StoreManagerInterface $storeManager;
    private ProductMetaData $productMetaData;
    private PriceTableResolverProxy $priceTableResolver;
    private CatalogRulePrice $catalogPriceResourceModel;

    public function __construct(
        ResourceConnection $resourceModel,
        StoreManagerInterface $storeManager,
        ProductMetaData $productMetaData,
        CatalogConfig $catalogSettings,
        CatalogRulePrice $catalogPriceResourceModel,
        PriceTableResolverProxy $priceTableResolver
    ) {
        $this->resource = $resourceModel;
        $this->storeManager = $storeManager;
        $this->productMetaData = $productMetaData;
        $this->priceTableResolver = $priceTableResolver;
        $this->settings = $catalogSettings;
        $this->catalogPriceResourceModel = $catalogPriceResourceModel;
    }

    /**
     * @throws NoSuchEntityException
     */
    public function loadPriceDataFromPriceIndex(int $storeId, array $productIds): array
    {
        $entityIdField = $this->productMetaData->getIdentifierField();
        $websiteId = (int)$this->storeManager->getStore($storeId)->getWebsiteId();

        $priceIndexTableName = $this->priceTableResolver->resolve($websiteId, self::DEFAULT_CUSTOMER_GROUP_ID);

        $select = $this->resource->getConnection()->select()
            ->from(
                ['p' => $priceIndexTableName],
                [
                    $entityIdField,
                    'price',
                    'final_price',
                ]
            )
            ->where('p.customer_group_id = ?', self::DEFAULT_CUSTOMER_GROUP_ID)
            ->where('p.website_id = ?', $websiteId)
            ->where("p.$entityIdField IN (?)", $productIds);

        $indexedPrices = $this->resource->getConnection()->fetchAssoc($select);

        if ($this->settings->useCatalogPriceRules()) {
            $catalogRulePrices = $this->getCatalogRulePrices($websiteId, $productIds);

            foreach ($catalogRulePrices as $productId => $catalogRulePrice) {
                if (isset($indexedPrices[$productId]['final_price'])) {
                    $indexedPrices[$productId]['final_price'] = min($indexedPrices[$productId]['final_price'], $catalogRulePrice);
                } else {
                    $indexedPrices[$productId]['final_price'] = $catalogRulePrice;
                }
            }
        }

        return $indexedPrices;
    }

    private function getCatalogRulePrices(int $websiteId, array $productsIds): array
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select();
        $select->join(
            ['cpiw' => $this->catalogPriceResourceModel->getTable('catalog_product_index_website')],
            'cpiw.website_id = i.website_id',
            []
        );
        $select->join(
            ['cpp' => $this->catalogPriceResourceModel->getMainTable()],
            'cpp.website_id = cpiw.website_id'
            . ' AND cpp.rule_date = cpiw.website_date',
            []
        );

        $select->where('cpp.product_id IN (?)', $productsIds);
        $select->where('cpp.customer_group_id = ?', self::DEFAULT_CUSTOMER_GROUP_ID);
        $select->where('cpp.website_id = ?', $websiteId);
        $select->columns([
            'product_id' => 'cpp.product_id',
            'final_price' => 'cpp.rule_price',
        ]);

        return $connection->fetchPairs($select);
    }

}

<?php declare(strict_types = 1);

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Product;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\CatalogRule\Model\ResourceModel\Rule\Product\Price as CatalogRulePrice;

use StreamX\ConnectorCatalog\Model\Product\PricesResolver;
use StreamX\ConnectorCatalog\Model\SystemConfig\CatalogConfig;

class IndexedPrices
{
    /**
     * Only default customer Group ID (0) is supported now
     */
    private const CUSTOMER_GROUP_ID = 0;

    private ResourceConnection $resource;
    private CatalogConfig $settings;
    private StoreManagerInterface $storeManager;
    private CatalogRulePrice $catalogPriceResourceModel;
    private PricesResolver $pricesResolver;

    public function __construct(
        ResourceConnection $resourceModel,
        StoreManagerInterface $storeManager,
        CatalogConfig $catalogSettings,
        CatalogRulePrice $catalogPriceResourceModel,
        PricesResolver $pricesResolver
    ) {
        $this->resource = $resourceModel;
        $this->storeManager = $storeManager;
        $this->settings = $catalogSettings;
        $this->catalogPriceResourceModel = $catalogPriceResourceModel;
        $this->pricesResolver = $pricesResolver;
    }

    /**
     * Only default customer Group ID (0) is supported now
     *
     * @throws NoSuchEntityException
     */
    public function loadIndexedPrices(int $storeId, array $productIds): array
    {
        $websiteId = (int)$this->storeManager->getStore($storeId)->getWebsiteId();
        $indexedPrices = $this->pricesResolver->loadIndexedPrices($productIds);

        if ($this->settings->useCatalogPriceRules()) {
            $catalogPrices = $this->getCatalogRulePrices($websiteId, $productIds);

            foreach ($catalogPrices as $productId => $catalogPrice) {
                $indexedPrice = $indexedPrices[$productId]['final_price'] ?? $catalogPrice;
                $indexedPrices[$productId]['final_price'] = min($catalogPrice, $indexedPrice);
            }
        }

        return $indexedPrices;
    }

    private function getCatalogRulePrices(int $websiteId, array $productsIds): array
    {
        $connection = $this->resource->getConnection();

        $select = $connection
            ->select()
            ->join(
                ['cpiw' => $this->catalogPriceResourceModel->getTable('catalog_product_index_website')],
                'cpiw.website_id = i.website_id',
                []
            )->join(
                ['cpp' => $this->catalogPriceResourceModel->getMainTable()], // default table name is: catalogrule_product_price
                'cpp.website_id = cpiw.website_id AND cpp.rule_date = cpiw.website_date',
                []
            )->where('cpp.product_id IN (?)', $productsIds)
            ->where('cpp.customer_group_id = ?', self::CUSTOMER_GROUP_ID)
            ->where("cpp.website_id = $websiteId")
            ->columns([
                'product_id' => 'cpp.product_id',
                'final_price' => 'cpp.rule_price',
            ]);

        return $connection->fetchPairs($select);
    }

}
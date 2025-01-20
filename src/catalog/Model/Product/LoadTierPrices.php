<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Product;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use StreamX\ConnectorCatalog\Model\SystemConfig\CatalogConfig;
use StreamX\ConnectorCatalog\Model\ProductMetaData;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\TierPrices as TierPricesResource;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\ProductAttributesProvider;

use Magento\Customer\Model\Group;
use Magento\Store\Model\StoreManagerInterface;

class LoadTierPrices
{
    private TierPricesResource $tierPriceResource;
    private ProductAttributesProvider $attributeDataProvider;
    private StoreManagerInterface $storeManager;
    private ProductMetaData $productMetaData;
    private CatalogConfig $configSettings;

    public function __construct(
        CatalogConfig $configSettings,
        TierPricesResource $tierPricesResource,
        StoreManagerInterface $storeManager,
        ProductMetaData $productMetaData,
        ProductAttributesProvider $config
    ) {
        $this->tierPriceResource = $tierPricesResource;
        $this->storeManager = $storeManager;
        $this->productMetaData = $productMetaData;
        $this->configSettings = $configSettings;
        $this->attributeDataProvider = $config;
    }

    /**
     * @throws Exception
     * @throws LocalizedException
     */
    public function execute(array $indexData, int $storeId): array
    {
        if ($this->syncTierPrices()) {
            $linkField = $this->productMetaData->get()->getLinkField();
            $linkFieldIds = array_column($indexData, $linkField);
            $websiteId = $this->getWebsiteId($storeId);

            $tierPrices = $this->tierPriceResource->loadTierPrices($websiteId, $linkFieldIds);
            /** @var \Magento\Catalog\Model\Product\Attribute\Backend\TierPrice $backend */
            $backend = $this->getTierPriceAttribute()->getBackend();

            foreach ($indexData as $productId => $product) {
                $linkFieldValue = $product[$linkField];

                if (isset($tierPrices[$linkFieldValue])) {
                    $tierRowsData = $tierPrices[$linkFieldValue];
                    $tierRowsData = $backend->preparePriceData(
                        $tierRowsData,
                        $indexData[$productId]['type_id'],
                        $websiteId
                    );

                    foreach ($tierRowsData as $tierRowData) {
                        if (Group::NOT_LOGGED_IN_ID === $tierRowData['cust_group'] && $tierRowData['price_qty'] == 1) {
                            $price = (float) $indexData[$productId]['price'];
                            $price = min((float) $tierRowData['price'], $price);
                            $indexData[$productId]['price'] = $price;
                        }

                        $indexData[$productId]['tier_prices'][] = $this->prepareTierPrices($tierRowData);
                    }
                } else {
                    $indexData[$productId]['tier_prices'] = [];
                }
            }
        }

        return $indexData;
    }

    private function syncTierPrices(): bool
    {
        return $this->configSettings->syncTierPrices();
    }

    private function prepareTierPrices(array $productTierPrice): array
    {
        return [
            'customer_group_id' => (int) $productTierPrice['cust_group'],
            'value' => (float) $productTierPrice['price'],
            'qty' => (float) $productTierPrice['price_qty'],
            'extension_attributes' => ['website_id' => (int) $productTierPrice['website_id']],
        ];
    }

    /**
     * @throws LocalizedException
     */
    private function getWebsiteId(int $storeId): int
    {
        $attribute = $this->getTierPriceAttribute();

        if ($attribute->isScopeGlobal()) {
            return 0;
        } elseif ($storeId) {
            return (int) ($this->storeManager->getStore($storeId)->getWebsiteId());
        }
    }

    /**
     * @return \Magento\Eav\Model\Entity\Attribute\AbstractAttribute
     * @throws LocalizedException
     */
    private function getTierPriceAttribute()
    {
        return $this->attributeDataProvider->getAttributeByCode('tier_price');
    }
}

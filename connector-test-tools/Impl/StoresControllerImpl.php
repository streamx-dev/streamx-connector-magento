<?php

namespace StreamX\ConnectorTestTools\Impl;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\Group;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use Magento\Store\Model\WebsiteFactory;
use Magento\Store\Model\GroupFactory;
use Magento\Store\Model\StoreFactory;
use StreamX\ConnectorTestTools\Api\StoresControllerInterface;

class StoresControllerImpl implements StoresControllerInterface
{
    private const STORE_2_CODE = 'store_2';

    private const SECOND_WEBSITE_CODE = 'second_website';
    private const STORE_FOR_SECOND_WEBSITE_CODE = 'store_for_second_website';

    private const STORE_2_PRODUCT_KEY_PREFIX = 'pim_store_2:';
    private const STORE_2_CATEGORY_KEY_PREFIX = 'cat_store_2:';

    private const SECOND_WEBSITE_PRODUCT_KEY_PREFIX = 'pim_website_2:';
    private const SECOND_WEBSITE_CATEGORY_KEY_PREFIX = 'cat_website_2:';

    private const PRODUCT_IDS_IN_SECOND_WEBSITE = [4, 5, 6, 61, 62]; // 4, 5, 6 are simple products. 61 is a variant of configurable product 62

    private const PRODUCT_KEY_PREFIX = 'streamx_connector_settings/streamx_client/product_key_prefix';
    private const CATEGORY_KEY_PREFIX = 'streamx_connector_settings/streamx_client/category_key_prefix';
    private const CONNECTOR_ENABLE_CONFIG_KEY = 'streamx_connector_settings/general_settings/enable';
    private const ALLOWED_STORES_CONFIG_KEY = 'streamx_connector_settings/general_settings/allowed_stores';

    private WebsiteFactory $websiteFactory;
    private GroupFactory $groupFactory;
    private StoreFactory $storeFactory;
    private StoreManagerInterface $storeManager;
    private WriterInterface $writer;
    private ResourceConnection $connection;

    public function __construct(
        WebsiteFactory $websiteFactory,
        GroupFactory $groupFactory,
        StoreFactory $storeFactory,
        StoreManagerInterface $storeManager,
        WriterInterface $writer,
        ResourceConnection $connection
    ) {
        $this->websiteFactory = $websiteFactory;
        $this->groupFactory = $groupFactory;
        $this->storeFactory = $storeFactory;
        $this->storeManager = $storeManager;
        $this->writer = $writer;
        $this->connection = $connection;
    }

    /**
     * @inheritdoc
     */
    public function setUpStoresAndWebsites(): bool {
        // make sure StreamX Connector is turned on
        $this->setGlobalLevelConfigValue(self::CONNECTOR_ENABLE_CONFIG_KEY, 1);

        if (count($this->storeManager->getWebsites()) == 2) {
            // assuming all is already set up
            return false;
        }

        $defaultWebsite = array_values($this->storeManager->getWebsites())[0];
        $defaultStoreId = $this->storeManager->getStore('default')->getId();

        $store2 = $this->createStore($defaultWebsite->getId(), self::STORE_2_CODE);
        $secondWebsite = $this->createWebsite(self::SECOND_WEBSITE_CODE);
        $storeForSecondWebsite = $this->createStore($secondWebsite->getId(), self::STORE_FOR_SECOND_WEBSITE_CODE);

        // configure keys
        $this->setStoreLevelConfigValue(self::PRODUCT_KEY_PREFIX, self::STORE_2_PRODUCT_KEY_PREFIX, $store2);
        $this->setStoreLevelConfigValue(self::CATEGORY_KEY_PREFIX, self::STORE_2_CATEGORY_KEY_PREFIX, $store2);

        $this->setWebsiteLevelConfigValue(self::PRODUCT_KEY_PREFIX, self::SECOND_WEBSITE_PRODUCT_KEY_PREFIX, $secondWebsite);
        $this->setWebsiteLevelConfigValue(self::CATEGORY_KEY_PREFIX, self::SECOND_WEBSITE_CATEGORY_KEY_PREFIX, $secondWebsite);

        // configure exported stores
        $this->setWebsiteLevelConfigValue(self::ALLOWED_STORES_CONFIG_KEY, $defaultStoreId . ',' . $store2->getId(), $defaultWebsite);
        $this->setWebsiteLevelConfigValue(self::ALLOWED_STORES_CONFIG_KEY, $storeForSecondWebsite->getId(), $secondWebsite);

        // add products to the new website
        foreach (self::PRODUCT_IDS_IN_SECOND_WEBSITE as $productId) {
            $this->connection->getConnection()->insert('catalog_product_website', [
                'product_id' => $productId,
                'website_id' => $secondWebsite->getId(),
            ]);
        }

        return true;
    }

    private function createStore(int $websiteId, string $code): Store {
        $storeGroup = $this->createStoreGroup($websiteId, $code);

        $store = $this->storeFactory->create()
            ->setCode($code . '_view')
            ->setWebsiteId($websiteId)
            ->setStoreGroupId($storeGroup->getId())
            ->setName(strtoupper($code . '_view'))
            ->setIsActive(true)
            ->save();

        $storeGroup->setDefaultStoreId($store->getId())->save();

        return $store;
    }

    private function createStoreGroup(int $websiteId, string $code): Group {
        return $this->groupFactory->create()
            ->setWebsiteId($websiteId)
            ->setCode($code)
            ->setName(strtoupper($code))
            ->setRootCategoryId(2)
            ->save();
    }

    private function createWebsite(string $code): Website {
        return $this->websiteFactory->create()
            ->setCode($code)
            ->setName(strtoupper($code))
            ->save();
    }

    private function setStoreLevelConfigValue(string $key, string $value, Store $store): void {
        $this->writer->save($key, $value, ScopeInterface::SCOPE_STORES, $store->getId());
    }

    private function setWebsiteLevelConfigValue(string $key, string $value, WebsiteInterface $website): void {
        $this->writer->save($key, $value, ScopeInterface::SCOPE_WEBSITES, $website->getId());
    }

    private function setGlobalLevelConfigValue(string $key, string $value): void {
        $this->writer->save($key, $value);
    }
}

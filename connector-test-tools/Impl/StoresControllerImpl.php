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
    private const STORE_CODE_FOR_SECOND_WEBSITE = 'store_for_second_website';

    private const PRODUCT_IDS_IN_SECOND_WEBSITE = [4, 5, 6, 61, 62]; // 4, 5, 6 are simple products. 61 is a variant of configurable product 62

    private const CONNECTOR_ENABLE_CONFIG_KEY = 'streamx_connector_settings/general_settings/enable';
    private const RABBIT_MQ_ENABLE_CONFIG_KEY = 'streamx_connector_settings/rabbit_mq/enable';
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

        // make sure RabbitMQ is enabled. For tests, please execute "bin/magento streamx:consumer:start" manually
        $this->setGlobalLevelConfigValue(self::RABBIT_MQ_ENABLE_CONFIG_KEY, 1);

        if (count($this->storeManager->getWebsites()) == 2) {
            // assuming all is already set up
            return false;
        }

        $defaultWebsite = array_values($this->storeManager->getWebsites())[0];
        $defaultStoreId = $this->storeManager->getStore('default')->getId();

        $store2 = $this->createStore($defaultWebsite->getId(), self::STORE_2_CODE, self::STORE_2_CODE);
        $secondWebsite = $this->createWebsite(self::SECOND_WEBSITE_CODE);
        $storeForSecondWebsite = $this->createStore($secondWebsite->getId(), self::STORE_CODE_FOR_SECOND_WEBSITE, self::STORE_CODE_FOR_SECOND_WEBSITE);

        // configure exported stores
        $this->setIndexedStoresForWebsite($defaultStoreId . ',' . $store2->getId(), $defaultWebsite);
        $this->setIndexedStoresForWebsite($storeForSecondWebsite->getId(), $secondWebsite);

        // add products to the new website
        foreach (self::PRODUCT_IDS_IN_SECOND_WEBSITE as $productId) {
            $this->connection->getConnection()->insert('catalog_product_website', [
                'product_id' => $productId,
                'website_id' => $secondWebsite->getId(),
            ]);
        }

        return true;
    }

    private function createStore(int $websiteId, string $storeCode, string $viewCode): Store {
        $store = $this->createStoreGroup($websiteId, $storeCode);

        $storeView = $this->storeFactory->create()
            ->setCode($viewCode)
            ->setWebsiteId($websiteId)
            ->setStoreGroupId($store->getId())
            ->setName(self::codeToName($viewCode))
            ->setIsActive(true)
            ->save();

        $store->setDefaultStoreId($storeView->getId())->save();

        return $storeView;
    }

    private function createStoreGroup(int $websiteId, string $code): Group {
        return $this->groupFactory->create()
            ->setWebsiteId($websiteId)
            ->setCode($code)
            ->setName(self::codeToName($code))
            ->setRootCategoryId(2)
            ->save();
    }

    private function createWebsite(string $code): Website {
        return $this->websiteFactory->create()
            ->setCode($code)
            ->setName(self::codeToName($code))
            ->save();
    }

    private static function codeToName(string $code): string {
        return strtoupper($code);
    }

    private function setIndexedStoresForWebsite(string $value, WebsiteInterface $website): void {
        $this->writer->save(
            self::ALLOWED_STORES_CONFIG_KEY,
            $value,
            ScopeInterface::SCOPE_WEBSITES,
            $website->getId()
        );
    }

    private function setGlobalLevelConfigValue(string $key, string $value): void {
        $this->writer->save($key, $value);
    }
}

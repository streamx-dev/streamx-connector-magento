<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Indexer;

use Magento\Store\Api\Data\StoreInterface;
use StreamX\ConnectorCore\System\GeneralConfig;
use Magento\Store\Model\StoreManagerInterface;

class IndexedStoresProvider
{
    private StoreManagerInterface $storeManager;
    private GeneralConfig $generalSettings;

    public function __construct(
        GeneralConfig $generalSettings,
        StoreManagerInterface $storeManager
    ) {
        $this->generalSettings = $generalSettings;
        $this->storeManager = $storeManager;
    }

    /**
     * @return StoreInterface[]
     */
    public function getStores(): array
    {
        $indexedStores = [];

        $allStores = $this->storeManager->getStores();
        foreach ($allStores as $store) {
            $storeId = (int)$store->getId();
            $websiteId = (int)$store->getWebsiteId();

            $indexedStoresForWebsite = $this->generalSettings->getIndexedStores($websiteId);
            if (in_array($storeId, $indexedStoresForWebsite)) {
                $indexedStores[] = $store;
            }
        }

        return $indexedStores;
    }
}

<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Indexer;

use Magento\Store\Api\Data\StoreInterface;
use StreamX\ConnectorCore\System\GeneralConfig;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Responsible for getting stores allowed to reindex
 */
class IndexableStoresProvider
{
    private StoreManagerInterface $storeManager;
    private GeneralConfig $generalSettings;
    private ?array $loadedStores = null;

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
        if ($this->loadedStores) {
            return $this->loadedStores;
        }

        $websiteId = (int) $this->storeManager->getStore()->getWebsiteId();
        $allowedStoreIds = $this->generalSettings->getStoresToIndex($websiteId);
        $allowedStores = [];

        foreach ($this->storeManager->getStores() as $store) {
            if (in_array($store->getId(), $allowedStoreIds)) {
                $allowedStores[] = $store;
            }
        }

        return $this->loadedStores = $allowedStores;
    }

    public function override(array $stores): void
    {
        $this->loadedStores = $stores;
    }
}

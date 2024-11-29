<?php

namespace Divante\VsbridgeIndexerCore\Indexer;

use Divante\VsbridgeIndexerCore\System\GeneralConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Responsible for getting stores allowed to reindex
 */
class StoreManager
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var GeneralConfigInterface
     */
    private $generalSettings;

    /**
     * @var array|null
     */
    private $loadedStores = null;

    public function __construct(
        GeneralConfigInterface $generalSettings,
        StoreManagerInterface $storeManager
    ) {
        $this->generalSettings = $generalSettings;
        $this->storeManager = $storeManager;
    }

    /**
     * @return array|\Magento\Store\Api\Data\StoreInterface[]
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStores(int $storeId = null)
    {
        if ($this->loadedStores) {
            return $this->loadedStores;
        }

        $allowedStoreIds = $this->generalSettings->getStoresToIndex();
        $allowedStores = [];

        if (null === $storeId) {
            $stores = $this->storeManager->getStores();
        } else {
            $stores = [$this->storeManager->getStore($storeId)];
        }

        foreach ($stores as $store) {
            if (in_array($store->getId(), $allowedStoreIds)) {
                $allowedStores[] = $store;
            }
        }

        return $this->loadedStores = $allowedStores;
    }

    public function override(array $stores)
    {
        $this->loadedStores = $stores;
    }
}

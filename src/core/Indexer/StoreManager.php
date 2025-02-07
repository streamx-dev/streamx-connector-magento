<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Indexer;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use StreamX\ConnectorCore\System\GeneralConfig;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Responsible for getting stores allowed to reindex
 */
class StoreManager
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
     * @throws NoSuchEntityException
     */
    public function getStores(int $storeId = null): array
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

    public function override(array $stores): void
    {
        $this->loadedStores = $stores;
    }
}

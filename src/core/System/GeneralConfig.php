<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\System;

use Magento\Framework\App\Config\ScopeConfigInterface;

class GeneralConfig implements GeneralConfigInterface
{
    private ScopeConfigInterface $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @inheritdoc
     */
    public function canReindexStore(int $storeId): bool
    {
        $allowedStores = $this->getStoresToIndex();

        if (in_array($storeId, $allowedStores)) {
            return true;
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function getStoresToIndex(): array
    {
        $stores = $this->scopeConfig->getValue(self::XML_PATH_ALLOWED_STORES_TO_REINDEX);

        if (null === $stores || '' === $stores) {
            $stores = [];
        } else {
            $stores = explode(',', $stores);
        }

        return $stores;
    }

    /**
     * @inheritdoc
     */
    public function isEnabled(): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(self::XML_PATH_GENERAL_CONNECTOR_ENABLED);
    }
}

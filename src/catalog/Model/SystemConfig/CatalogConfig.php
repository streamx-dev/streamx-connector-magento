<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\SystemConfig;

use Magento\Framework\App\Config\ScopeConfigInterface;
use StreamX\ConnectorCore\Api\BaseConfigurationReader;

class CatalogConfig extends BaseConfigurationReader
{
    public function __construct(ScopeConfigInterface $scopeConfig) {
        parent::__construct($scopeConfig, 'catalog_settings');
    }

    public function useUrlKeyToGenerateSlug(): bool {
        return (bool)$this->getGlobalConfigValue('use_url_key_to_generate_slug');
    }

    public function useUrlKeyAndIdToGenerateSlug(): bool {
        return (bool)$this->getGlobalConfigValue('use_url_key_and_id_to_generate_slug');
    }

    public function useCatalogRules(): bool {
        return (bool)$this->getGlobalConfigValue('use_catalog_rules');
    }

    public function getAllowedProductTypes(): array {
        return parent::splitCommaSeparatedValueToArray(
            $this->getGlobalConfigValue('allowed_product_types')
        );
    }

    public function getProductAttributesToIndex(int $storeId): array {
        return parent::splitCommaSeparatedValueToArray(
            $this->getStoreLevelConfigValue('product_attributes', $storeId)
        );
    }

    public function getChildProductAttributesToIndex(int $storeId): array {
        return parent::splitCommaSeparatedValueToArray(
            $this->getStoreLevelConfigValue('child_product_attributes', $storeId)
        );
    }
}

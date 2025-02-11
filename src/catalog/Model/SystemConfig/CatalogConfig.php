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
        return $this->getBoolConfigValue('use_url_key_to_generate_slug');
    }

    public function useUrlKeyAndIdToGenerateSlug(): bool {
        return $this->getBoolConfigValue('use_url_key_and_id_to_generate_slug');
    }

    public function useCatalogRules(): bool {
        return $this->getBoolConfigValue('use_catalog_rules');
    }

    public function getAllowedProductTypes(int $storeId): array {
        return $this->getArrayConfigValue('allowed_product_types', $storeId);
    }

    public function getProductAttributesToIndex(int $storeId): array {
        return $this->getArrayConfigValue('product_attributes', $storeId);
    }

    public function getChildProductAttributesToIndex(int $storeId): array {
        return $this->getArrayConfigValue('child_product_attributes', $storeId);
    }
}

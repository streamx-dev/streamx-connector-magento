<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\SystemConfig;

use Magento\Catalog\Model\Config;
use Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use StreamX\ConnectorCatalog\Model\ResourceModel\ProductConfig as ConfigResource;

class CategoryConfig implements CategoryConfigInterface
{
    private array $settings = [];
    private array $attributesSortBy = [];
    private ScopeConfigInterface $scopeConfig;
    private ConfigResource $catalogConfigResource;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ConfigResource $configResource
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->catalogConfigResource = $configResource;
    }

    /**
     * @inheritdoc
     */
    public function getAttributesUsedForSortBy(): array
    {
        if (empty($this->attributesSortBy)) {
            $attributes = $this->catalogConfigResource->getAttributesUsedForSortBy();
            $attributes[] = 'position';

            $this->attributesSortBy = $attributes;
        }

        return $this->attributesSortBy;
    }

    /**
     * @inheritdoc
     */
    public function getProductListDefaultSortBy(int $storeId): string
    {
        $path = Config::XML_PATH_LIST_DEFAULT_SORT_BY;
        $key = $path . (string) $storeId;

        if (!isset($this->settings[$key])) {
            $sortBy = $this->scopeConfig->getValue(
                $path,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );

            $this->settings[$key] = (string) $sortBy;
        }

        return $this->settings[$key];
    }

    /**
     * @inheritdoc
     */
    public function getCategoryUrlSuffix(int $storeId): string
    {
        $key = sprintf(
            '%s_%s',
            CategoryUrlPathGenerator::XML_PATH_CATEGORY_URL_SUFFIX,
            $storeId
        );

        if (!isset($this->settings[$key])) {
            $configValue = $this->scopeConfig->getValue(
                CategoryUrlPathGenerator::XML_PATH_CATEGORY_URL_SUFFIX,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
            $this->settings[$key] = (string) $configValue;
        }

        return $this->settings[$key];
    }
}

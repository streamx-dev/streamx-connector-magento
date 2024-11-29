<?php declare(strict_types=1);

namespace Divante\VsbridgeIndexerCatalog\Setup\UpgradeData;

use Divante\VsbridgeIndexerCatalog\Api\CatalogConfigurationInterface;
use Magento\Framework\Exception\LocalizedException;

class SetProductDefaultAttributes
{
    /**
     * @var UpdateAttributesInConfigurationFactory
     */
    private $updateAttributesInConfiguration;

    /**
     * @var array
     */
    private $productAttributes;

    /**
     * @var array
     */
    private $childAttributes;

    public function __construct(
        UpdateAttributesInConfigurationFactory $updateAttributesInConfiguration,
        array $productAttributes,
        array $childAttributes
    ) {
        $this->childAttributes = $childAttributes;
        $this->productAttributes = $productAttributes;
        $this->updateAttributesInConfiguration = $updateAttributesInConfiguration;
    }

    /**
     * @throws LocalizedException
     */
    public function execute()
    {
        /** @var UpdateAttributesInConfiguration $updateConfiguration */
        $updateConfiguration = $this->updateAttributesInConfiguration->create(['entityType' => 'catalog_product']);

        $updateConfiguration->execute(
            $this->productAttributes,
            $this->getConfigPath(CatalogConfigurationInterface::PRODUCT_ATTRIBUTES)
        );

        $updateConfiguration->execute(
            $this->childAttributes,
            $this->getConfigPath(CatalogConfigurationInterface::CHILD_ATTRIBUTES)
        );
    }

    /**
     * @return string
     */
    private function getConfigPath(string $configField): string
    {
        return CatalogConfigurationInterface::CATALOG_SETTINGS_XML_PREFIX . '/' . $configField;
    }
}

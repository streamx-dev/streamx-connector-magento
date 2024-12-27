<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Setup\UpgradeData;

use StreamX\ConnectorCatalog\Api\CatalogConfigurationInterface;
use Magento\Framework\Exception\LocalizedException;

class SetProductDefaultAttributes
{
    private UpdateAttributesInConfigurationFactory $updateAttributesInConfiguration;
    private array $productAttributes;
    private array $childAttributes;

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

    private function getConfigPath(string $configField): string
    {
        return CatalogConfigurationInterface::CATALOG_SETTINGS_XML_PREFIX . '/' . $configField;
    }
}

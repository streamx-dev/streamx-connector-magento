<?php

declare(strict_types=1);

namespace Divante\VsbridgeIndexerCatalog\Setup\UpgradeData;

use Divante\VsbridgeIndexerCatalog\Model\SystemConfig\CategoryConfigInterface;
use Magento\Framework\Exception\LocalizedException;

class SetCategoryDefaultAttributes
{
    /**
     * @var UpdateAttributesInConfigurationFactory
     */
    private $updateAttributesInConfiguration;

    /**
     * @var array
     */
    private $mainAttributes;

    /**
     * @var array
     */
    private $childAttributes;

    public function __construct(
        UpdateAttributesInConfigurationFactory $updateAttributesInConfiguration,
        array $mainAttributes = [],
        array $childAttributes = []
    ) {
        $this->updateAttributesInConfiguration = $updateAttributesInConfiguration;
        $this->childAttributes = $childAttributes;
        $this->mainAttributes = $mainAttributes;
    }

    /**
     * @throws LocalizedException
     */
    public function execute()
    {
        /** @var UpdateAttributesInConfiguration $updateConfiguration */
        $updateConfiguration = $this->updateAttributesInConfiguration->create(['entityType' => 'catalog_category']);

        if (!empty($this->mainAttributes)) {
            $updateConfiguration->execute(
                $this->mainAttributes,
                $this->getConfigPath(CategoryConfigInterface::CATEGORY_ATTRIBUTES)
            );
        }

        if (!empty($this->childAttributes)) {
            $updateConfiguration->execute(
                $this->childAttributes,
                $this->getConfigPath(CategoryConfigInterface::CHILD_ATTRIBUTES)
            );
        }
    }

    private function getConfigPath(string $configField): string
    {
        return CategoryConfigInterface::CATEGORY_SETTINGS_XML_PREFIX . '/' . $configField;
    }
}

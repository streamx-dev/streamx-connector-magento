<?php

declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Setup\UpgradeData;

use Magento\Config\Model\ResourceModel\Config;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;

class UpdateAttributesInConfiguration
{
    private Config $resourceConfig;
    private EavConfig $eavConfig;
    private string $entityType;

    public function __construct(
        Config $resourceConfig,
        EavConfig $eavConfig,
        string $entityType
    ) {
        $this->entityType = $entityType;
        $this->eavConfig = $eavConfig;
        $this->resourceConfig = $resourceConfig;
    }

    /**
     * @throws LocalizedException
     */
    public function execute(array $attributeCodes, string $path): void
    {
        $attributeIds = $this->getAttributeIdsByCodes($attributeCodes);

        if (!empty($attributeIds)) {
            $this->resourceConfig->saveConfig(
                $path,
                $attributeIds,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                0
            );
        }
    }

    /**
     * @throws LocalizedException
     */
    private function getAttributeIdsByCodes(array $attributes): string
    {
        $attributeIds = [];

        foreach ($attributes as $attributeCode) {
            $attribute = $this->eavConfig->getAttribute($this->entityType, $attributeCode);

            if ($attribute->getId()) {
                $attributeIds[] = $attribute->getId();
            }
        }

        return implode(',', $attributeIds);
    }
}

<?php

namespace Divante\VsbridgeIndexerCatalog\Setup;

use Divante\VsbridgeIndexerCatalog\Setup\UpgradeData\SetCategoryDefaultAttributes;
use Divante\VsbridgeIndexerCatalog\Setup\UpgradeData\SetProductDefaultAttributes;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var SetProductDefaultAttributes
     */
    private $setProductDefaultAttributes;

    /**
     * @var SetCategoryDefaultAttributes
     */
    private $setCategoryDefaultAttributes;

    public function __construct(
        SetProductDefaultAttributes $setDefaultAttributes,
        SetCategoryDefaultAttributes $setCategoryDefaultAttributes
    ) {
        $this->setProductDefaultAttributes = $setDefaultAttributes;
        $this->setCategoryDefaultAttributes = $setCategoryDefaultAttributes;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.2.0', '<')) {
            $this->setProductDefaultAttributes->execute();
        }

        if (version_compare($context->getVersion(), '1.3.0', '<')) {
            $this->setCategoryDefaultAttributes->execute();
        }
    }
}

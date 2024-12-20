<?php

namespace StreamX\ConnectorCatalog\Setup;

use StreamX\ConnectorCatalog\Setup\UpgradeData\SetProductDefaultAttributes;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

class UpgradeData implements UpgradeDataInterface
{
    private SetProductDefaultAttributes $setProductDefaultAttributes;

    public function __construct(SetProductDefaultAttributes $setDefaultAttributes) {
        $this->setProductDefaultAttributes = $setDefaultAttributes;
    }

    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.2.0', '<')) {
            $this->setProductDefaultAttributes->execute();
        }
    }
}

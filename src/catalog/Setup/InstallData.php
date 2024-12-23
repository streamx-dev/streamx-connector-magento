<?php

namespace StreamX\ConnectorCatalog\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use StreamX\ConnectorCatalog\Setup\InstallData\SetProductDefaultAttributes;

class InstallData implements InstallDataInterface
{
    private SetProductDefaultAttributes $setProductDefaultAttributes;

    public function __construct(SetProductDefaultAttributes $setDefaultAttributes) {
        $this->setProductDefaultAttributes = $setDefaultAttributes;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context): void
    {
        $this->setProductDefaultAttributes->execute();
    }
}

<?php

namespace StreamX\ConnectorCatalog\test\integration\utils;

use function shell_exec;

class MagentoOperationsExecutor {

    private string $magentoFolder;

    public function __construct() {
        $this->magentoFolder = FileUtils::findFolder('magento');
    }

    public function executeCommand(string $command): ?string {
        $cdCommand = 'cd ' . $this->magentoFolder;
        $magentoCommand = "bin/magento $command";
        return shell_exec("$cdCommand && $magentoCommand");
    }
}
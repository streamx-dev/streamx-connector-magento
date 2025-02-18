<?php

namespace StreamX\ConnectorCatalog\test\integration\utils;

use DateTime;
use function shell_exec;

class MagentoOperationsExecutor {

    private string $magentoFolder;

    public function __construct() {
        $this->magentoFolder = FileUtils::findFolder('magento');
    }

    public function executeCommand(string $command): ?string {
        $startTime = new DateTime();

        $cdCommand = 'cd ' . $this->magentoFolder;
        $magentoCommand = "bin/magento $command";
        $result = shell_exec("$cdCommand && $magentoCommand");

        $endTime = new DateTime();
        $diff = $startTime->diff($endTime);
        echo "The command $command took " . $diff->format('%s.%F') . " seconds\n";

        return $result;
    }
}
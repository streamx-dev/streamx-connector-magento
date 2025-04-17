<?php

namespace StreamX\ConnectorCatalog\test\integration\utils;

use DateTime;

class MagentoOperationsExecutor {

    private static ?string $magentoFolder = null;

    public static function executeCommand(string $command): ?string {
        if (self::$magentoFolder === null) {
            self::$magentoFolder = FileUtils::findFolder('magento');
        }
        $startTime = new DateTime();

        $cdCommand = 'cd ' . self::$magentoFolder;
        $magentoCommand = "bin/magento $command";
        $result = shell_exec("$cdCommand && $magentoCommand");

        $endTime = new DateTime();
        $diff = $startTime->diff($endTime);
        echo $diff->format('%s.%F') . " seconds elapsed for $command\n";

        return $result;
    }
}
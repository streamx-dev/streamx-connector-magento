<?php

namespace StreamX\ConnectorCatalog\test\integration\utils;

use Exception;
use function shell_exec;

class MagentoIndexerOperationsExecutor {

    private const INDEXER_NAME = 'streamx_product_indexer';

    public const UPDATE_ON_SAVE_DISPLAY_NAME = 'Update on Save';
    public const UPDATE_BY_SCHEDULE_DISPLAY_NAME = 'Update by Schedule';

    private const UPDATE_ON_SAVE_INTERNAL_NAME = 'realtime';
    private const UPDATE_BY_SCHEDULE_INTERNAL_NAME = 'schedule';

    private string $magentoFolder;

    public function __construct() {
        $this->magentoFolder = self::findMagentoFolder();
    }

    public function setProductIndexerModeToUpdateOnSave(): void {
        $this->executeCommand("set-mode " . self::UPDATE_ON_SAVE_INTERNAL_NAME);
    }

    public function setProductIndexerModeToUpdateBySchedule(): void {
        $this->executeCommand("set-mode " . self::UPDATE_BY_SCHEDULE_INTERNAL_NAME);
    }

    public function executeCommand(string $indexerCommand): ?string {
        $cdCommand = 'cd ' . $this->magentoFolder;
        $magentoCommand = 'bin/magento indexer:' . $indexerCommand . ' ' . self::INDEXER_NAME;
        return shell_exec("$cdCommand && $magentoCommand");
    }

    private static function findMagentoFolder(): string {
        $currentDir = __DIR__; // Start from the current directory

        while (true) {
            if (is_dir("$currentDir/magento")) {
                return "$currentDir/magento";
            }

            $parentDir = dirname($currentDir);

            if ($parentDir === $currentDir) { // root dir reached
                break;
            }

            $currentDir = $parentDir;
        }

        throw new Exception("magento folder not found");
    }

}
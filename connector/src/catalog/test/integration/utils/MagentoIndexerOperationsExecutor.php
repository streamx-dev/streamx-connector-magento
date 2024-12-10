<?php

namespace StreamX\ConnectorCatalog\test\integration\utils;

use Exception;
use function shell_exec;

class MagentoIndexerOperationsExecutor {

    public const UPDATE_ON_SAVE_DISPLAY_NAME = 'Update on Save';
    public const UPDATE_BY_SCHEDULE_DISPLAY_NAME = 'Update by Schedule';

    private const UPDATE_ON_SAVE_INTERNAL_NAME = 'realtime';
    private const UPDATE_BY_SCHEDULE_INTERNAL_NAME = 'schedule';

    private string $magentoFolder;
    private string $indexerName;

    public function __construct(string $indexerName) {
        $this->magentoFolder = self::findMagentoFolder();
        $this->indexerName = $indexerName;
    }

    public function setProductIndexerModeToUpdateOnSave(): void {
        $this->executeCommand("set-mode " . self::UPDATE_ON_SAVE_INTERNAL_NAME);
    }

    public function setProductIndexerModeToUpdateBySchedule(): void {
        $this->executeCommand("set-mode " . self::UPDATE_BY_SCHEDULE_INTERNAL_NAME);
    }

    public function reindex(): void {
        $this->executeCommand('reindex');
    }

    public function executeCommand(string $indexerCommand): ?string {
        $cdCommand = 'cd ' . $this->magentoFolder;
        $magentoCommand = 'bin/magento indexer:' . $indexerCommand . ' ' . $this->indexerName;
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
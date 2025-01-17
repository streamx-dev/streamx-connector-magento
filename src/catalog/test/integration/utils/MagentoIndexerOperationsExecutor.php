<?php

namespace StreamX\ConnectorCatalog\test\integration\utils;

use function shell_exec;

class MagentoIndexerOperationsExecutor {

    public const UPDATE_ON_SAVE_DISPLAY_NAME = 'Update on Save';
    public const UPDATE_BY_SCHEDULE_DISPLAY_NAME = 'Update by Schedule';

    private const INDEXER_MODE_NAME_MAPPINGS = [
        self::UPDATE_ON_SAVE_DISPLAY_NAME => 'realtime',
        self::UPDATE_BY_SCHEDULE_DISPLAY_NAME => 'schedule'
    ];

    private string $magentoFolder;
    private string $indexerName;

    public function __construct(string $indexerName) {
        $this->magentoFolder = DirectoryUtils::findFolder('magento');
        $this->indexerName = $indexerName;
    }

    /**
     * @return string display name of the indexer mode
     */
    public function getIndexerMode(): string {
        $modeString = $this->executeCommand('show-mode'); // return value is in form: "Indexer Name:    Mode Display Name"
        $parts = explode(':', $modeString);
        return trim($parts[1]);
    }

    public function setIndexerMode(string $modeDisplayName): void {
        $modeInternalName = self::INDEXER_MODE_NAME_MAPPINGS[$modeDisplayName];
        $this->executeCommand("set-mode $modeInternalName");
    }

    private function executeCommand(string $indexerCommand): ?string {
        $cdCommand = 'cd ' . $this->magentoFolder;
        $magentoCommand = 'bin/magento indexer:' . $indexerCommand . ' ' . $this->indexerName;
        return shell_exec("$cdCommand && $magentoCommand");
    }

    public function replaceTextInMagentoFile(string $pathRelativeToMagentoFolder, string $from, string $to): void {
        $filePath = "$this->magentoFolder/$pathRelativeToMagentoFolder";
        $content = file_get_contents($filePath);
        $newContent = str_replace($from, $to, $content);
        file_put_contents($filePath, $newContent);
    }
}
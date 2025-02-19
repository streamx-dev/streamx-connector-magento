<?php

namespace StreamX\ConnectorCatalog\test\integration\utils;

class MagentoIndexerOperationsExecutor extends MagentoOperationsExecutor {

    public const UPDATE_ON_SAVE_DISPLAY_NAME = 'Update on Save';
    public const UPDATE_BY_SCHEDULE_DISPLAY_NAME = 'Update by Schedule';

    private const INDEXER_MODE_NAME_MAPPINGS = [
        self::UPDATE_ON_SAVE_DISPLAY_NAME => 'realtime',
        self::UPDATE_BY_SCHEDULE_DISPLAY_NAME => 'schedule'
    ];

    private string $indexerName;

    public function __construct(string $indexerName) {
        parent::__construct();
        $this->indexerName = $indexerName;
    }

    /**
     * @return string display name of the indexer mode
     */
    public function getIndexerMode(): string {
        $modeString = $this->executeIndexerCommand('show-mode'); // return value is in form: "Indexer Name:    Mode Display Name"
        $parts = explode(':', $modeString);
        return trim($parts[1]);
    }

    public function setIndexerMode(string $modeDisplayName): void {
        $modeInternalName = self::INDEXER_MODE_NAME_MAPPINGS[$modeDisplayName];
        $this->executeIndexerCommand("set-mode $modeInternalName");
    }

    public function flushCache(): void {
        $this->executeCommand('cache:flush');
    }

    private function executeIndexerCommand(string $indexerCommand): ?string {
        return parent::executeCommand("indexer:$indexerCommand $this->indexerName");
    }
}
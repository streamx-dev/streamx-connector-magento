<?php

namespace StreamX\ConnectorCatalog\test\integration\utils;

class MagentoIndexerOperationsExecutor extends MagentoOperationsExecutor {

    public const UPDATE_ON_SAVE_DISPLAY_NAME = 'Update on Save';
    public const UPDATE_BY_SCHEDULE_DISPLAY_NAME = 'Update by Schedule';

    private const INDEXER_MODE_NAME_MAPPINGS = [
        self::UPDATE_ON_SAVE_DISPLAY_NAME => 'realtime',
        self::UPDATE_BY_SCHEDULE_DISPLAY_NAME => 'schedule'
    ];

    /**
     * @return string display name of the indexer mode
     */
    public static function getIndexerMode(string $indexerName): string {
        $modeString = parent::executeCommand("indexer:show-mode $indexerName"); // return value is in form: "Indexer Name:    Mode Display Name"
        $parts = explode(':', $modeString);
        return trim($parts[1]);
    }

    public static function setIndexerMode(string $indexerName, string $modeDisplayName): void {
        $modeInternalName = self::INDEXER_MODE_NAME_MAPPINGS[$modeDisplayName];
        parent::executeCommand("indexer:set-mode $modeInternalName $indexerName");
    }

}
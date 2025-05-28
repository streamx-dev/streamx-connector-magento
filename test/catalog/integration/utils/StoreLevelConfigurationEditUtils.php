<?php

namespace StreamX\ConnectorCatalog\test\integration\utils;

class StoreLevelConfigurationEditUtils {

    private function __construct() {
        // no instances
    }

    public static function setConfigurationValue(string $configurationFieldPath, int $storeId, string $value): void {
        MagentoEndpointsCaller::call('configuration/store/set', [
            'configurationFieldPath' => $configurationFieldPath,
            'storeId' => $storeId,
            'value' => $value
        ]);
    }

    public static function removeConfigurationValue(string $configurationFieldPath, int $storeId): void {
        MagentoEndpointsCaller::call('configuration/store/remove', [
            'configurationFieldPath' => $configurationFieldPath,
            'storeId' => $storeId
        ]);
    }
}
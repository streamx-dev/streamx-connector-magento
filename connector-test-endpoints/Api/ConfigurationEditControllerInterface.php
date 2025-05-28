<?php

namespace StreamX\ConnectorTestEndpoints\Api;

use Exception;

interface ConfigurationEditControllerInterface {

    /**
     * Sets the provided global configuration value
     * @param string $configurationFieldPath Configuration field path
     * @param string $value Value to assign
     * @return void
     * @throws Exception
     */
    public function setGlobalConfigurationValue(string $configurationFieldPath, string $value): void;

    /**
     * Sets the provided store-level configuration value
     * @param string $configurationFieldPath Configuration field path
     * @param int $storeId Store ID
     * @param string $value Value to assign
     * @return void
     * @throws Exception
     */
    public function setStoreLevelConfigurationValue(string $configurationFieldPath, int $storeId, string $value): void;

    /**
     * Removes store-level configuration value at the provided path
     * @param string $configurationFieldPath Configuration field path
     * @param int $storeId Store ID
     * @return void
     * @throws Exception
     */
    public function removeStoreLevelConfigurationValue(string $configurationFieldPath, int $storeId): void;
}

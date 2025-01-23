<?php

namespace StreamX\ConnectorTestTools\Api;

use Exception;

interface ConfigurationEditControllerInterface {

    /**
     * Sets the provided configuration value
     * @param string $configurationFieldPath Configuration field path
     * @param string $value Value to assign
     * @return void
     * @throws Exception
     */
    public function setConfigurationValue(string $configurationFieldPath, string $value): void;
}

<?php

namespace StreamX\ConnectorTestTools\Api;

interface StoresControllerInterface {

    /**
     * Sets up additional stores and websites required by integration tests.
     * @return bool false if the data was already present or true if the data was created
     */
    public function setUpStoresAndWebsites(): bool;
}

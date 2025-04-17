<?php

namespace StreamX\ConnectorTestTools\Api;

interface StoresControllerInterface {

    /**
     * Sets up additional stores and websites required by integration tests.
     * @return void
     */
    public function setUpStoresAndWebsites(): void;
}

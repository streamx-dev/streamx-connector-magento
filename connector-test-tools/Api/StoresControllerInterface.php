<?php

namespace StreamX\ConnectorTestTools\Api;

interface StoresControllerInterface {

    /**
     * Sets up additional stores and websites required by integration tests.
     * The endpoint also enables StreamX Connector and RabbitMQ
     * @return void
     */
    public function setUpStoresAndWebsites(): void;
}

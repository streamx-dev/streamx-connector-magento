<?php

namespace StreamX\ConnectorTestEndpoints\Api;

interface ObserverRunnerInterface {

    /**
     * Executes the given observer
     * @param string $observerClassName The observer fully qualified class name
     * @return void
     */
    public function execute(string $observerClassName): void;
}

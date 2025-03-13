<?php

namespace StreamX\ConnectorTestTools\Api;

interface TestDataControllerInterface {

    /**
     * Imports test products and categories from a csv file resource to Magento
     * @return void
     */
    public function importTestProducts(): void;
}

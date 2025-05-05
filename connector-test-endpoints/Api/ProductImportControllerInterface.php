<?php

namespace StreamX\ConnectorTestEndpoints\Api;

interface ProductImportControllerInterface {

    /**
     * Imports products and categories from csv to Magento
     * @param string $csvContent valid CSV with products to be imported, in syntax expected by Magento
     * @param string $behavior one of: add_update / replace / delete
     * @return void
     */
    public function importProducts(string $csvContent, string $behavior): void;
}
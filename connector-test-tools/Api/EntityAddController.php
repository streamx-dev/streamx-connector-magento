<?php

namespace StreamX\ConnectorTestTools\Api;

use Exception;

interface EntityAddController {

    /**
     * Adds a product
     * @param string $productName The display name for the new product
     * @param int $categoryId Id of the category for the new product
     * @return int ID of the inserted product
     * @throws Exception
     */
    public function addProduct(string $productName, int $categoryId): int;
}

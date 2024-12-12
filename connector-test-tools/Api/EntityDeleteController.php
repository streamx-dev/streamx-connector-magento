<?php

namespace StreamX\ConnectorTestTools\Api;

use Exception;

interface EntityDeleteController {

    /**
     * Deletes a product
     * @param int $productId ID of the product to be renamed
     * @return void
     * @throws Exception
     */
    public function deleteProduct(int $productId): void;
}

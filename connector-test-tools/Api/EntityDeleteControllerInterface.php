<?php

namespace StreamX\ConnectorTestTools\Api;

use Exception;

interface EntityDeleteControllerInterface {

    /**
     * Deletes a product
     * @param int $productId ID of the product to be renamed
     * @return void
     * @throws Exception
     */
    public function deleteProduct(int $productId): void;
}

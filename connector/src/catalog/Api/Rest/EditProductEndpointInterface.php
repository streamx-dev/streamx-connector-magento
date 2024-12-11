<?php

namespace StreamX\ConnectorCatalog\Api\Rest;

use Exception;

interface EditProductEndpointInterface {

    /**
     * Renames a product
     * @param int $productId ID of the product to be renamed
     * @param string $newName New name
     * @return void
     * @throws Exception
     */
    public function renameProduct(int $productId, string $newName);
}

<?php

namespace StreamX\ConnectorTestEndpoints\Api;

use Exception;

interface EntityDeleteControllerInterface {

    /**
     * Deletes a product
     * @param int $productId ID of the product to be renamed
     * @return void
     * @throws Exception
     */
    public function deleteProduct(int $productId): void;

    /**
     * Deletes a category
     * @param int $categoryId ID of the category to be renamed
     * @return void
     * @throws Exception
     */
    public function deleteCategory(int $categoryId): void;

    /**
     * Deletes an attribute
     * @param int $attributeId ID of the attribute to be removed
     * @return void
     * @throws Exception
     */
    public function deleteAttribute(int $attributeId): void;
}

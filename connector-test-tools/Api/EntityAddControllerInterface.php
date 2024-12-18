<?php

namespace StreamX\ConnectorTestTools\Api;

use Exception;

interface EntityAddControllerInterface {

    /**
     * Adds a product
     * @param string $productName The display name for the new product
     * @param int $categoryId Id of the category for the new product
     * @return int ID of the inserted product
     * @throws Exception
     */
    public function addProduct(string $productName, int $categoryId): int;

    /**
     * Adds a category
     * @param string $categoryName The display name for the new category
     * @return int ID of the inserted category
     * @throws Exception
     */
    public function addCategory(string $categoryName): int;

    /**
     * Adds a product attribute
     * @param string $attributeCode Internal code of the new attribute
     * @return int ID of the inserted attribute
     * @throws Exception
     */
    public function addAttribute(string $attributeCode): int;
}

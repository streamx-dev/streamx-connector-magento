<?php

namespace StreamX\ConnectorTestTools\Api;

use Exception;

interface EntityAddControllerInterface {

    /**
     * Adds a product
     * @param string $productName The display name for the new product
     * @param int[] $categoryIds IDs of the categories for the new product
     * @return int ID of the inserted product
     * @throws Exception
     */
    public function addProduct(string $productName, array $categoryIds): int;

    /**
     * Adds a category
     * @param string $categoryName The display name for the new category
     * @param int $parentCategoryId Parent category ID
     * @return int ID of the inserted category
     * @throws Exception
     */
    public function addCategory(string $categoryName, int $parentCategoryId): int;

    /**
     * Adds a product attribute of type text
     * @param string $attributeCode Internal code of the new attribute
     * @return int ID of the inserted attribute
     * @throws Exception
     */
    public function addTextAttribute(string $attributeCode): int;

    /**
     * Adds a product attribute that can have multiple values
     * @param string $attributeCode Internal code of the new attribute
     * @param string[] $values initial valid values of the attribute
     * @return int ID of the inserted attribute
     * @throws Exception
     */
    public function addMultiValuedAttribute(string $attributeCode, array $values): int;

    /**
     * Adds a product attribute that contains option
     * @param string $attributeCode Internal code of the new attribute
     * @param string[] $options valid options of the attribute
     * @return int ID of the inserted attribute
     * @throws Exception
     */
    public function addAttributeWithOptions(string $attributeCode, array $options): int;

    /**
     * Adds a product attribute and assigns it to the given product
     * @param string $attributeCode Internal code of the new attribute
     * @param int $productId ID of the product to add the attribute to
     * @return int ID of the inserted attribute
     * @throws Exception
     */
    public function addAttributeAndAssignToProduct(string $attributeCode, int $productId): int;
}

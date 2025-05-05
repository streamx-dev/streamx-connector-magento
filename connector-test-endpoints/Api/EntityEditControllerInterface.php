<?php

namespace StreamX\ConnectorTestEndpoints\Api;

use Exception;

interface EntityEditControllerInterface {

    /**
     * Renames a product
     * @param int $productId ID of the product to be renamed
     * @param string $newName New name
     * @return void
     * @throws Exception
     */
    public function renameProduct(int $productId, string $newName): void;

    /**
     * Renames a category
     * @param int $categoryId ID of the category to be renamed
     * @param string $newName New name
     * @return void
     * @throws Exception
     */
    public function renameCategory(int $categoryId, string $newName): void;

    /**
     * Renames frontend label of a product attribute
     * @param string $attributeCode code of the attribute to be renamed
     * @param string $newName New name
     * @return void
     * @throws Exception
     */
    public function renameAttribute(string $attributeCode, string $newName): void;

    /**
     * Changes the given category of a product to another category
     * @param int $productId ID of the product to be changed
     * @param int $oldCategoryId ID of an existing category of a product
     * @param int $newCategoryId ID of a category to be assigned instead of $oldCategoryId
     * @return void
     * @throws Exception
     */
    public function changeProductCategory(int $productId, int $oldCategoryId, int $newCategoryId): void;

    /**
     * Changes value of the given attribute of a product
     * @param int $productId ID of the product to be changed
     * @param string $attributeCode Code of the attribute
     * @param string $newValue New value of the attribute to be set
     * @return void
     */
    public function changeProductAttribute(int $productId, string $attributeCode, string $newValue): void;

    /**
     * Changes value of the given attribute of a category
     * @param int $categoryId ID of the category to be changed
     * @param string $attributeCode Code of the attribute
     * @param string $newValue New value of the attribute to be set
     * @return void
     */
    public function changeCategoryAttribute(int $categoryId, string $attributeCode, string $newValue): void;

    /**
     * Adds the given product to the category
     * @param int $categoryId ID of the category to be changed
     * @param int $productId ID of the product to be added to category
     * @return void
     */
    public function addProductToCategory(int $categoryId, int $productId): void;

    /**
     * Removes the given product from the category
     * @param int $categoryId ID of the category to be changed
     * @param int $productId ID of the product to be removed from category
     * @return void
     */
    public function removeProductFromCategory(int $categoryId, int $productId): void;
}

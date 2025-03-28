<?php

namespace StreamX\ConnectorTestTools\Api;

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
}

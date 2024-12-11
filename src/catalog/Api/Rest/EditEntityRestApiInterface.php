<?php

namespace StreamX\ConnectorCatalog\Api\Rest;

use Exception;

interface EditEntityRestApiInterface {

    /**
     * Renames a product
     * @param int $productId ID of the product to be renamed
     * @param string $newName New name
     * @return void
     * @throws Exception
     */
    public function renameProduct(int $productId, string $newName);

    /**
     * Renames a category
     * @param int $categoryId ID of the category to be renamed
     * @param string $newName New name
     * @return void
     * @throws Exception
     */
    public function renameCategory(int $categoryId, string $newName);

    /**
     * Renames frontend label of a product attribute
     * @param string $attributeCode code of the attribute to be renamed
     * @param string $newName New name
     * @return void
     * @throws Exception
     */
    public function renameAttribute(string $attributeCode, string $newName);
}

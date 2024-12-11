<?php

namespace StreamX\ConnectorCatalog\Api\Rest;

use Exception;

interface EditCategoryEndpointInterface {

    /**
     * Renames a category
     * @param int $categoryId ID of the category to be renamed
     * @param string $newName New name
     * @return void
     * @throws Exception
     */
    public function renameCategory(int $categoryId, string $newName);
}

<?php

declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\ResourceModel\Category;

use Magento\Framework\DB\Select;

interface BaseSelectModifierInterface
{
    /**
     * Modify the select statement
     */
    public function execute(Select $select, int $storeId): Select;
}

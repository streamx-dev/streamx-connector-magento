<?php

declare(strict_types=1);

namespace Divante\VsbridgeIndexerCatalog\Model\ResourceModel\Category;

use Magento\Framework\DB\Select;

interface BaseSelectModifierInterface
{
    /**
     * Modify the select statement
     *
     * @param Select $select
     * @param int $storeId
     *
     * @return Select
     */
    public function execute(Select $select, int $storeId): Select;
}

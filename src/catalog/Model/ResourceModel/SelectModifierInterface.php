<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\ResourceModel;

use Magento\Framework\DB\Select;

interface SelectModifierInterface
{
    /**
     * Modify the select statement
     */
    public function modify(Select $select, int $storeId): void;
}
